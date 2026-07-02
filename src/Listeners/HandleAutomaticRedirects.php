<?php

namespace Aerni\AdvancedSeo\Listeners;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Enums\RedirectType;
use Aerni\AdvancedSeo\Facades\Redirects;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Entries\GetDateFromPath;
use Statamic\Entries\GetSlugFromPath;
use Statamic\Events\EntrySaved;
use Statamic\Events\EntrySaving;
use Statamic\Events\TermSaved;
use Statamic\Facades\Blink;
use Statamic\Facades\Site;
use Statamic\Support\Str;

class HandleAutomaticRedirects
{
    /**
     * Capture the entry's original URL before the save overwrites the
     * initial path, and stash it for handleEntrySaved.
     */
    public function handleEntrySaving(EntrySaving $event): void
    {
        $entry = $event->entry;

        if (! $this->shouldHandleEntry($entry)) {
            return;
        }

        /**
         * The original slug and date survive in the initial file path,
         * unlike getOriginal(), which re-syncs on every Stache fetch.
         */
        if (! $initialPath = $entry->initialPath()) {
            return;
        }

        $originalSlug = (new GetSlugFromPath)($initialPath);
        $originalDate = (new GetDateFromPath)($initialPath);

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
     * Once the save went through and the new URL is known, create the
     * redirect and bring the existing redirects in line with the change.
     */
    public function handleEntrySaved(EntrySaved $event): void
    {
        $entry = $event->entry;

        if (! $originalPath = Blink::pull($this->blinkKey($entry))) {
            return;
        }

        if (! $url = $entry->absoluteUrlWithoutRedirect()) {
            return;
        }

        $currentPath = $entry->site()->relativePath($url);

        if ($originalPath === $currentPath) {
            return;
        }

        $site = $entry->locale();
        $destination = "entry::{$entry->id()}";

        $this->createRedirect($originalPath, $destination, $site);
        $this->repointStaleRedirectDestinations($originalPath, $destination, $site);
        $this->deleteShadowingRedirects($currentPath, $site);
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

        if (! $this->shouldHandleTerm($term)) {
            return;
        }

        $originalSlug = $term->getOriginal('slug');
        $newSlug = $term->slug();

        // Skip new terms (no original) and unchanged slugs.
        if (! $originalSlug || $originalSlug === $newSlug) {
            return;
        }

        $newPaths = $this->termPathsPerSite($term);
        $term->slug($originalSlug);
        $originalPaths = $this->termPathsPerSite($term);
        $term->slug($newSlug);

        foreach ($newPaths as $site => $newPath) {
            $originalPath = $originalPaths[$site] ?? null;

            if (! $originalPath || $originalPath === $newPath) {
                continue;
            }

            $this->createRedirect($originalPath, $newPath, $site);
            $this->repointStaleRedirectDestinations($originalPath, $newPath, $site);
            $this->deleteShadowingRedirects($newPath, $site);
        }
    }

    protected function shouldHandleEntry(Entry $entry): bool
    {
        if (! RedirectsFeature::enabled()) {
            return false;
        }

        return (bool) Seo::find("collections::{$entry->collectionHandle()}")?->config()->value('redirects');
    }

    protected function shouldHandleTerm(Term $term): bool
    {
        if (! RedirectsFeature::enabled()) {
            return false;
        }

        return (bool) Seo::find("taxonomies::{$term->taxonomyHandle()}")?->config()->value('redirects');
    }

    /**
     * The site-relative path of the term in each of its localizations, in the
     * term's current state. Localizations without a URL are omitted.
     *
     * @return array<string, string>
     */
    protected function termPathsPerSite(Term $term): array
    {
        return $term->taxonomy()->sites()
            ->mapWithKeys(function ($site) use ($term) {
                $url = $term->in($site)?->urlWithoutRedirect();

                return [$site => $url ? $this->siteRelativePath($url, $site) : null];
            })
            ->filter()
            ->all();
    }

    /**
     * Strip the site's path prefix so the path matches how the resolver keys
     * redirects (a term url includes the prefix, e.g. "/fr/tags/news").
     */
    protected function siteRelativePath(string $url, string $site): string
    {
        $prefix = rtrim(parse_url(Site::get($site)->url(), PHP_URL_PATH) ?? '', '/');
        $path = parse_url($url, PHP_URL_PATH) ?? $url;

        if ($prefix && Str::startsWith($path, $prefix)) {
            $path = Str::removeLeft($path, $prefix);
        }

        return $path ?: '/';
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

    /**
     * Create the redirect from the old to the new URL. An existing redirect
     * with the same source is a deliberate editor decision and wins.
     */
    protected function createRedirect(string $source, string $destination, string $site): void
    {
        $existing = Redirects::query()
            ->where('site', $site)
            ->where('source', $source)
            ->first();

        if ($existing) {
            return;
        }

        Redirects::make()
            ->source($source)
            ->destination($destination)
            ->type(RedirectType::Permanent)
            ->site($site)
            ->description(__('advanced-seo::messages.redirect_automatic_description'))
            ->save();
    }

    /**
     * A path destination equal to the old URL is stale: following it would now
     * take two hops (A → old → new). Point those redirects straight at the entry.
     * Destinations holding an entry reference resolve live and never go stale.
     */
    protected function repointStaleRedirectDestinations(string $staleDestination, string $destination, string $site): void
    {
        Redirects::query()
            ->where('site', $site)
            ->where('destination', $staleDestination)
            ->get()
            ->each(fn (Redirect $redirect) => $redirect->destination($destination)->save());
    }

    /**
     * A redirect whose source is the new URL shadows the now-live page and
     * would misfire once the URL 404s again (e.g. after renaming back).
     */
    protected function deleteShadowingRedirects(string $source, string $site): void
    {
        Redirects::query()
            ->where('site', $site)
            ->where('source', $source)
            ->get()
            ->each(fn (Redirect $redirect) => $redirect->delete());
    }

    protected function blinkKey(Entry $entry): string
    {
        return "advanced-seo::automatic-redirect::{$entry->id()}";
    }
}
