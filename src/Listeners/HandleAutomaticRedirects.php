<?php

namespace Aerni\AdvancedSeo\Listeners;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Enums\RedirectType;
use Aerni\AdvancedSeo\Facades\Redirects;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Statamic\Contracts\Entries\Entry;
use Statamic\Entries\GetDateFromPath;
use Statamic\Entries\GetSlugFromPath;
use Statamic\Events\EntrySaved;
use Statamic\Events\EntrySaving;
use Statamic\Facades\Blink;

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

    protected function shouldHandleEntry(Entry $entry): bool
    {
        if (! RedirectsFeature::enabled()) {
            return false;
        }

        return (bool) Seo::find("collections::{$entry->collectionHandle()}")?->config()->value('redirects');
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
