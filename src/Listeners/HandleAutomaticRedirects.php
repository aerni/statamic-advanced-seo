<?php

namespace Aerni\AdvancedSeo\Listeners;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Enums\RedirectOrigin;
use Aerni\AdvancedSeo\Enums\RedirectResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Entries\GetDateFromPath;
use Statamic\Entries\GetSlugFromPath;
use Statamic\Events\CollectionTreeEntriesMovedOrRemoved;
use Statamic\Events\CollectionTreeSaved;
use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;
use Statamic\Events\EntrySaving;
use Statamic\Events\TermDeleted;
use Statamic\Events\TermSaved;
use Statamic\Facades\Blink;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\Stache;

/**
 * Creates and maintains redirects automatically as entry and term URLs change.
 *
 * Capture is keyed on the slug, and for dated collections the date, the only
 * pre-save state the flat-file driver reliably exposes (getOriginal() returns
 * the new value for other fields on the Stache driver). A tree move never
 * saves the entry, so the previous URI is read from storage
 * (CollectionTreeEntriesMovedOrRemoved still sees it; CollectionTreeSaved
 * runs after Statamic has written the new one). A parent slug or date change
 * does not save the descendants, so their previous URIs are stashed on the parent
 * save and their stored URIs are rewritten before the redirects are created.
 *
 * URL changes from any other source are out of scope and left to the editor
 * to redirect manually:
 *
 * - a route token that reads a non-slug field, since the old value can't be
 *   recovered on the flat-file driver;
 * - a collection route or mount change, which changes every entry's URL at once.
 */
class HandleAutomaticRedirects
{
    /**
     * Stash the original paths before the save overwrites them, so
     * handleEntrySaved can create the redirects.
     */
    public function handleEntrySaving(EntrySaving $event): void
    {
        $this->stashEntry($event->entry);
        $this->stashDescendants($event->entry);
    }

    /**
     * Once the save went through, redirect the entry and its descendants
     * from the paths stashed above.
     */
    public function handleEntrySaved(EntrySaved $event): void
    {
        $this->redirectEntry($event->entry);
        $this->redirectDescendants($event->entry);
    }

    /**
     * The tree is already the new shape, but stored URIs still describe the
     * previous one. Stash those paths and create the redirects once Statamic
     * has rewritten them on CollectionTreeSaved.
     */
    public function handleCollectionTreeEntriesMovedOrRemoved(CollectionTreeEntriesMovedOrRemoved $event): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        $this->stashOriginalPaths($event->moved, $this->treeBlinkKey());
    }

    /**
     * Runs after Statamic's UpdateStructuredEntryUris listener, which is
     * registered earlier. Forget the URI caches anyway so a stale Blink
     * value from before the move can't hide the new path.
     */
    public function handleCollectionTreeSaved(CollectionTreeSaved $event): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        if (! $paths = Blink::pull($this->treeBlinkKey())) {
            return;
        }

        Blink::store('entry-uris')->flush();
        Blink::store('structure-uris')->flush();

        foreach ($paths as $id => $originalPath) {
            $this->processEntryRedirect(EntryFacade::find($id), $originalPath);
        }
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

        $this->deleteAutomaticRedirectsTo("entry::{$event->entry->id()}");
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

        // A term owns its URL in every site, so delete any automatic redirect that uses it.
        foreach ($currentPaths as $site => $currentPath) {
            $this->deleteAutomaticRedirectsFrom($currentPath, $site);
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
            $this->deleteAutomaticRedirectsTo($path, $site);
        }
    }

    /**
     * The feature flag and the collection or taxonomy toggle.
     */
    protected function redirectsEnabled(Entry|Term $item): bool
    {
        if (! RedirectsFeature::enabled()) {
            return false;
        }

        $handle = match (true) {
            $item instanceof Entry => "collections::{$item->collectionHandle()}",
            $item instanceof Term => "taxonomies::{$item->taxonomyHandle()}",
        };

        return (bool) Seo::find($handle)?->config()->value('redirects');
    }

    /**
     * Whether this item itself gets a redirect. Descendants are checked on
     * their own, so a false result here does not stop the rest of the save.
     */
    protected function shouldHandle(Entry|Term $item): bool
    {
        if (! $this->redirectsEnabled($item)) {
            return false;
        }

        // Only a live item owns its URL; draft, scheduled and expired entries
        // aren't publicly reachable. Terms are always published.
        return $item->status() === 'published';
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
     * The pre-save slug and date when either differs from the entry now.
     * Null when this save does not move a URL.
     *
     * @return array{0: string, 1: ?string}|null
     */
    protected function changedSlugAndDate(Entry $entry): ?array
    {
        [$originalSlug, $originalDate] = $this->originalSlugAndDate($entry);

        if (! $originalSlug) {
            return null;
        }

        $slugChanged = $originalSlug !== $entry->slug();
        $dateChanged = $originalDate && $originalDate !== $this->currentDate($entry, $originalDate);

        if (! $slugChanged && ! $dateChanged) {
            return null;
        }

        return [$originalSlug, $originalDate];
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

    protected function entryBlinkKey(Entry $entry): string
    {
        return "advanced-seo::automatic-redirect::{$entry->id()}";
    }

    protected function descendantBlinkKey(Entry $entry): string
    {
        return "advanced-seo::automatic-redirect-descendants::{$entry->id()}";
    }

    /**
     * Stash this entry's original URL when its slug or date changed and the
     * entry itself should get a redirect.
     */
    protected function stashEntry(Entry $entry): void
    {
        if (! $this->shouldHandle($entry)) {
            return;
        }

        if (! $changed = $this->changedSlugAndDate($entry)) {
            return;
        }

        [$originalSlug, $originalDate] = $changed;

        if (! $originalUrl = $this->originalUrl($entry, $originalSlug, $originalDate)) {
            return;
        }

        Blink::put($this->entryBlinkKey($entry), $entry->site()->relativePath($originalUrl));
    }

    /**
     * A parent slug or date change moves every descendant. Stash the path
     * still stored for each published one.
     */
    protected function stashDescendants(Entry $entry): void
    {
        $page = $entry->page();

        if (! $page) {
            return;
        }

        if (! $this->changedSlugAndDate($entry)) {
            return;
        }

        $this->stashOriginalPaths(
            $page->flattenedPages()->map->id()->all(),
            $this->descendantBlinkKey($entry),
        );
    }

    /**
     * Redirect the entry from the path stashed on save.
     */
    protected function redirectEntry(Entry $entry): void
    {
        $this->processEntryRedirect($entry, Blink::pull($this->entryBlinkKey($entry)));
    }

    /**
     * Statamic does not rewrite descendant URIs on this save, so do that
     * before redirecting from the paths stashed above.
     */
    protected function redirectDescendants(Entry $entry): void
    {
        if (! $paths = Blink::pull($this->descendantBlinkKey($entry))) {
            return;
        }

        Blink::store('entry-uris')->flush();
        Blink::store('structure-uris')->flush();
        Blink::store('structure-entries')->flush();
        Blink::store('structure-entries')->put($entry->id(), $entry);

        $entry->collection()->updateEntryUris(array_keys($paths));

        Blink::store('entry-uris')->flush();
        Blink::store('structure-uris')->flush();

        foreach ($paths as $id => $originalPath) {
            $this->processEntryRedirect(EntryFacade::find($id), $originalPath);
        }
    }

    protected function treeBlinkKey(): string
    {
        return 'advanced-seo::automatic-redirect-tree';
    }

    /**
     * Stash the site-relative path still stored for each published entry.
     *
     * @param  array<int, string>  $ids
     */
    protected function stashOriginalPaths(array $ids, string $blinkKey): void
    {
        $paths = [];

        foreach ($ids as $id) {
            if (! $entry = EntryFacade::find($id)) {
                continue;
            }

            if (! $this->shouldHandle($entry)) {
                continue;
            }

            if (! $path = $this->originalPath($entry)) {
                continue;
            }

            $paths[$entry->id()] = $path;
        }

        Blink::put($blinkKey, $paths);
    }

    /**
     * Original site-relative path, read from storage rather than computed.
     *
     * By the time a tree move is observable, the in-memory
     * entry already resolves to the new URL (and a fresh request has an empty
     * Blink cache, so uri() cannot be trusted to return the old one). The
     * Stache URI index and the Eloquent uri column are still the previous
     * value until Statamic rewrites them after the save.
     */
    protected function originalPath(Entry $entry): ?string
    {
        $initialPath = $entry->initialPath();

        // Flat-file entries keep the previous URI in the Stache index.
        if ($initialPath) {
            $uri = Stache::store('entries')
                ->store($entry->collectionHandle())
                ->index('uri')
                ->get($entry->id());
        }

        // Eloquent entries keep it on the uri column. A custom file entry
        // may also define model(), so only read it when there is no file path.
        if (! $initialPath && method_exists($entry, 'model')) {
            $uri = $entry->model()?->getAttribute('uri');
        }

        return is_string($uri) && $uri !== '' ? $uri : null;
    }

    /**
     * Delete automatic redirects that use the entry's current URL, and when
     * an original path was stashed, create the redirect from it.
     */
    protected function processEntryRedirect(Entry $entry, ?string $originalPath = null): void
    {
        if (! $this->shouldHandle($entry)) {
            return;
        }

        if (! $currentPath = $this->currentPath($entry)) {
            return;
        }

        $this->deleteAutomaticRedirectsFrom($currentPath, $entry->locale());

        if ($originalPath !== null) {
            $this->createRedirectFromOriginalPath($entry, $originalPath, $currentPath);
        }
    }

    protected function currentPath(Entry $entry): ?string
    {
        // A URI blinked before this change would hide the new path.
        Blink::store('entry-uris')->forget($entry->id());

        $url = $entry->absoluteUrlWithoutRedirect();

        return $url ? $entry->site()->relativePath($url) : null;
    }

    protected function createRedirectFromOriginalPath(Entry $entry, string $originalPath, string $currentPath): void
    {
        if ($originalPath === $currentPath) {
            return;
        }

        if ($this->sourceIsOwnedByPublishedEntry($originalPath, $entry->locale())) {
            return;
        }

        $destination = "entry::{$entry->id()}";

        $this->createRedirect($originalPath, $destination, $entry->locale());
        $this->repointStaleRedirectDestinations($originalPath, $destination, $entry->locale());
    }

    /**
     * A source still owned by a published entry must remain routable and
     * must not be claimed by an automatic redirect.
     */
    protected function sourceIsOwnedByPublishedEntry(string $source, string $site): bool
    {
        return EntryFacade::query()
            ->where('site', $site)
            ->where('uri', $source)
            ->whereStatus('published')
            ->exists();
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
            ->whereSource($source)
            ->first();

        if ($existing) {
            return;
        }

        RedirectFacade::make()
            ->source($source)
            ->destination($destination)
            ->responseCode(RedirectResponseCode::Permanent)
            ->site($site)
            ->origin(RedirectOrigin::Automatic)
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
            ->where('origin', RedirectOrigin::Automatic->value)
            ->get()
            ->each(fn (Redirect $redirect) => $redirect->destination($destination)->save());
    }

    /**
     * Delete auto-created redirects whose source is this URL. The URL now
     * belongs to a live page, so the redirect would send the request away.
     * Manual redirects are the editor's to keep.
     */
    protected function deleteAutomaticRedirectsFrom(string $source, string $site): void
    {
        RedirectFacade::query()
            ->where('site', $site)
            ->whereSource($source)
            ->where('origin', RedirectOrigin::Automatic->value)
            ->get()
            ->each(fn (Redirect $redirect) => $redirect->delete());
    }

    /**
     * Delete the auto-created redirects that point at a given destination,
     * used to clear redirects left dangling by a deleted entry or term.
     */
    protected function deleteAutomaticRedirectsTo(string $destination, ?string $site = null): void
    {
        RedirectFacade::query()
            ->when($site, fn ($query) => $query->where('site', $site))
            ->where('destination', $destination)
            ->where('origin', RedirectOrigin::Automatic->value)
            ->get()
            ->each(fn (Redirect $redirect) => $redirect->delete());
    }
}
