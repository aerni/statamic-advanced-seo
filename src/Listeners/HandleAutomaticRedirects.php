<?php

namespace Aerni\AdvancedSeo\Listeners;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Enums\ResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Entries\GetDateFromPath;
use Statamic\Entries\GetSlugFromPath;
use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;
use Statamic\Events\EntrySaving;
use Statamic\Events\TermDeleted;
use Statamic\Events\TermSaved;
use Statamic\Facades\Blink;

/**
 * Creates and maintains redirects automatically as entry and term URLs change.
 *
 * Capture is keyed on the slug, and for dated collections the date, the only
 * pre-save state the flat-file driver reliably exposes (getOriginal() returns
 * the new value for other fields on the Stache driver). URL changes from any
 * other source are out of scope and left to the editor to redirect manually:
 *
 * - a route token that reads a non-slug field, since the old value can't be
 *   recovered on the flat-file driver;
 * - a structural tree move, which changes the URI without saving the entry;
 * - a parent slug rename, which changes every descendant's URL without saving
 *   the descendants;
 * - a collection route or mount change, which changes every entry's URL at once.
 */
class HandleAutomaticRedirects
{
    /**
     * Capture the entry's original URL before the save overwrites the
     * initial path, and stash it for handleEntrySaved.
     */
    public function handleEntrySaving(EntrySaving $event): void
    {
        $entry = $event->entry;

        if (! $this->shouldHandle($entry)) {
            return;
        }

        [$originalSlug, $originalDate] = $this->originalSlugAndDate($entry);

        if (! $originalSlug) {
            return;
        }

        $slugChanged = $originalSlug !== $entry->slug();
        $dateChanged = $originalDate && $originalDate !== $this->currentDate($entry, $originalDate);

        if (! $slugChanged && ! $dateChanged) {
            return;
        }

        if (! $originalUrl = $this->originalUrl($entry, $originalSlug, $originalDate)) {
            return;
        }

        Blink::put($this->blinkKey($entry), $entry->site()->relativePath($originalUrl));
    }

    /**
     * Once the save went through, free the entry's URL from any shadowing
     * redirect and, if the URL changed, create the redirect from the old one.
     */
    public function handleEntrySaved(EntrySaved $event): void
    {
        $entry = $event->entry;

        // Only a live entry owns its URL, so a draft's shadowing redirect must
        // stay (its URL still 404s) and no redirect is created from it.
        if (! $this->shouldHandle($entry) || $entry->status() !== 'published') {
            return;
        }

        if (! $url = $entry->absoluteUrlWithoutRedirect()) {
            return;
        }

        $currentPath = $entry->site()->relativePath($url);

        $this->deleteShadowingRedirects($currentPath, $entry->locale());

        if (! $originalPath = Blink::pull($this->blinkKey($entry))) {
            return;
        }

        if ($originalPath === $currentPath) {
            return;
        }

        $destination = "entry::{$entry->id()}";

        $this->createRedirect($originalPath, $destination, $entry->locale());
        $this->repointStaleRedirectDestinations($originalPath, $destination, $entry->locale());
    }

    /**
     * A deleted entry leaves the auto-created redirects that pointed at it with
     * a dead destination, so remove them. Manual redirects and redirects whose
     * source is the now-gone URL are left alone.
     */
    public function handleEntryDeleted(EntryDeleted $event): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        $this->deleteAutomaticRedirects("entry::{$event->entry->id()}");
    }

    /**
     * Terms can rely on getOriginal('slug') at TermSaved (unlike entries,
     * they aren't re-fetched and re-synced mid-save), so no saving-side
     * capture is needed. Terms carry no date and can't be an entry::
     * destination, so the redirect points at the new path.
     *
     * A base-slug change flows to every localization that inherits it, so a
     * redirect is created per site. Localizations with their own overridden
     * slug are unaffected and drop out (old URL equals new URL). An
     * independently changed localization slug is not detectable and is a
     * documented limitation.
     */
    public function handleTermSaved(TermSaved $event): void
    {
        $term = $event->term;

        if (! $this->shouldHandle($term)) {
            return;
        }

        $currentPaths = $this->pathsPerSite($term);

        // A term owns its URL in every site, so drop any auto-created redirect shadowing it.
        foreach ($currentPaths as $site => $currentPath) {
            $this->deleteShadowingRedirects($currentPath, $site);
        }

        $originalSlug = $term->getOriginal('slug');
        $newSlug = $term->slug();

        // Skip new terms (no original) and unchanged slugs.
        if (! $originalSlug || $originalSlug === $newSlug) {
            return;
        }

        $term->slug($originalSlug);
        $originalPaths = $this->pathsPerSite($term);
        $term->slug($newSlug);

        foreach ($currentPaths as $site => $currentPath) {
            $originalPath = $originalPaths[$site] ?? null;

            if (! $originalPath || $originalPath === $currentPath) {
                continue;
            }

            $this->createRedirect($originalPath, $currentPath, $site);
            $this->repointStaleRedirectDestinations($originalPath, $currentPath, $site);
        }
    }

    /**
     * A deleted term does the same, but its auto-created redirects point at a
     * path rather than an entry reference, so match on the term's URL per site.
     */
    public function handleTermDeleted(TermDeleted $event): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        foreach ($this->pathsPerSite($event->term) as $site => $path) {
            $this->deleteAutomaticRedirects($path, $site);
        }
    }

    protected function shouldHandle(Entry|Term $item): bool
    {
        if (! RedirectsFeature::enabled()) {
            return false;
        }

        // Only a live item owns its URL; draft, scheduled and expired entries
        // aren't publicly reachable. Terms are always published.
        if ($item->status() !== 'published') {
            return false;
        }

        $handle = match (true) {
            $item instanceof Entry => "collections::{$item->collectionHandle()}",
            $item instanceof Term => "taxonomies::{$item->taxonomyHandle()}",
        };

        return (bool) Seo::find($handle)?->config()->value('redirects');
    }

    /**
     * The entry's slug and date from before this save. The Stache driver
     * overwrites the initial file path on save, so its pre-save values live in
     * that path; getOriginal() is unreliable there because the Stache re-syncs
     * on fetch. The Eloquent driver has no file path but keeps getOriginal()
     * reliable, and it already stores the date in the same "Y-m-d-Hi" format.
     *
     * @return array{0: ?string, 1: ?string}
     */
    protected function originalSlugAndDate(Entry $entry): array
    {
        if ($initialPath = $entry->initialPath()) {
            return [(new GetSlugFromPath)($initialPath), (new GetDateFromPath)($initialPath)];
        }

        return [$entry->getOriginal('slug'), $entry->getOriginal('date')];
    }

    /**
     * The current date formatted at the original date's precision,
     * so the two can be compared as strings.
     */
    protected function currentDate(Entry $entry, string $originalDate): ?string
    {
        return $entry->date()?->format(match (strlen($originalDate)) {
            15 => 'Y-m-d-Hi',
            17 => 'Y-m-d-His',
            default => 'Y-m-d',
        });
    }

    /**
     * Let the routing compute the URL the entry had before this save by
     * temporarily restoring the original slug and date. The cached uri
     * has to be flushed for the swapped values to take effect.
     */
    protected function originalUrl(Entry $entry, string $originalSlug, ?string $originalDate): ?string
    {
        $newSlug = $entry->slug();
        $newDate = $entry->date();

        $entry->slug($originalSlug);

        if ($originalDate) {
            $entry->date($originalDate);
        }

        Blink::store('entry-uris')->forget($entry->id());

        $url = $entry->absoluteUrlWithoutRedirect();

        $entry->slug($newSlug);

        if ($originalDate) {
            $entry->date($newDate);
        }

        Blink::store('entry-uris')->forget($entry->id());

        return $url;
    }

    protected function blinkKey(Entry $entry): string
    {
        return "advanced-seo::automatic-redirect::{$entry->id()}";
    }

    /**
     * The site-relative path of the term in each of its localizations, in the
     * term's current state.
     *
     * @return array<string, string>
     */
    protected function pathsPerSite(Term $term): array
    {
        return $term->taxonomy()->sites()
            ->mapWithKeys(fn ($site) => [$site => $term->in($site)->uri()])
            ->all();
    }

    /**
     * Create the redirect from the old to the new URL. An existing redirect
     * with the same source is a deliberate editor decision and wins.
     */
    protected function createRedirect(string $source, string $destination, string $site): void
    {
        $existing = RedirectFacade::query()
            ->where('site', $site)
            ->where('source', $source)
            ->first();

        if ($existing) {
            return;
        }

        RedirectFacade::make()
            ->source($source)
            ->destination($destination)
            ->responseCode(ResponseCode::Permanent)
            ->site($site)
            ->automatic(true)
            ->save();
    }

    /**
     * An auto-created redirect whose destination is the old path is now stale:
     * following it would take two hops (A → old → new). Point it straight at the
     * new destination. Only auto-created redirects are repointed; manual ones are
     * the editor's to keep. Entry redirects use an entry reference that never goes
     * stale, so in practice this only flattens term chains.
     */
    protected function repointStaleRedirectDestinations(string $staleDestination, string $destination, string $site): void
    {
        RedirectFacade::query()
            ->where('site', $site)
            ->where('destination', $staleDestination)
            ->where('automatic', true)
            ->get()
            ->each(fn (Redirect $redirect) => $redirect->destination($destination)->save());
    }

    /**
     * An auto-created redirect whose source is now a live page shadows it and
     * would misfire (e.g. after renaming back, or once a new entry claims the
     * URL). Only auto-created redirects are removed; manual ones are the
     * editor's to keep.
     */
    protected function deleteShadowingRedirects(string $source, string $site): void
    {
        RedirectFacade::query()
            ->where('site', $site)
            ->where('source', $source)
            ->where('automatic', true)
            ->get()
            ->each(fn (Redirect $redirect) => $redirect->delete());
    }

    /**
     * Delete the auto-created redirects that point at a given destination,
     * used to clear redirects left dangling by a deleted entry or term.
     */
    protected function deleteAutomaticRedirects(string $destination, ?string $site = null): void
    {
        RedirectFacade::query()
            ->when($site, fn ($query) => $query->where('site', $site))
            ->where('destination', $destination)
            ->where('automatic', true)
            ->get()
            ->each(fn (Redirect $redirect) => $redirect->delete());
    }
}
