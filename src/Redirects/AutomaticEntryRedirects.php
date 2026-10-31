<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Carbon\Carbon;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Blink;
use Statamic\Facades\Entry as EntryFacade;

class AutomaticEntryRedirects
{
    /** @var array<string, string> */
    private array $entries = [];

    public function __construct(
        private EntryUrlChange $urls,
        private AutomaticRedirectWriter $redirects,
    ) {}

    public function stash(Entry $entry): void
    {
        $this->stashMovedByOwnChange($entry);
    }

    public function stashAffected(AffectedUrls $change): void
    {
        foreach ($change->localizations as $localized) {
            if ($this->wasLive($localized['entry']) && $localized['path']) {
                $this->entries += [$localized['entry']->id() => $localized['path']];
            }
        }
    }

    public function create(Entry $entry): void
    {
        $this->redirectEntry($entry);
    }

    public function createAffected(AffectedUrls $change): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        foreach ($change->entries as $id => $originalPath) {
            $this->processEntryRedirect(EntryFacade::find($id), $originalPath);
        }
    }

    /**
     * A deleted entry leaves the auto-created redirects that pointed at it with
     * a dead destination, so remove them. Manual redirects and redirects whose
     * source is the now-gone URL are left alone.
     */
    public function delete(Entry $entry): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        $this->redirects->deleteByDestination("entry::{$entry->id()}");
    }

    /**
     * Redirects are enabled, this entry is published, and its site has a route.
     * Child pages and localizations are checked on their own, so a false result
     * here does not stop the rest of the save.
     */
    private function isRedirectable(Entry $entry): bool
    {
        if (! RedirectsFeature::enabled()) {
            return false;
        }

        if (! Seo::find("collections::{$entry->collectionHandle()}")?->config()->value('redirects')) {
            return false;
        }

        // No route on this site means no public URL. A localization uses its own.
        if (! $entry->route()) {
            return false;
        }

        // Only a live entry owns its URL; draft, scheduled and expired entries
        // aren't publicly reachable.
        return $entry->status() === 'published';
    }

    /**
     * Stash the previous URL when the entry is live now and was live before.
     * After saving, the URL comparison decides whether it moved. Keep a path
     * already captured by the origin before it changed inherited values.
     */
    private function stashMovedByOwnChange(Entry $entry): void
    {
        if (! $this->isRedirectable($entry)) {
            return;
        }

        if (! $this->wasLive($entry)) {
            return;
        }

        $changed = $this->urls->changedValues($entry);

        if (! $originalUrl = $this->urls->originalUrl($entry, $changed)) {
            return;
        }

        $this->entries[$entry->id()] ??= $entry->site()->relativePath($originalUrl);
    }

    /**
     * The entry was publicly reachable before this save. The status on the
     * entry itself is already the new one.
     */
    private function wasLive(Entry $entry): bool
    {
        if (! $this->wasPublished($entry)) {
            return false;
        }

        $date = $this->parseDate($this->urls->originalDate($entry));

        $collection = $entry->collection();

        // A dated collection can hide a published entry when its date is private.
        if ($collection->dated() && $collection->futureDateBehavior() === 'private' && $date?->isFuture()) {
            return false;
        }

        if ($collection->dated() && $collection->pastDateBehavior() === 'private' && $date?->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Whether the entry was published before this save. The flag on the entry
     * itself is already the new one.
     */
    private function wasPublished(Entry $entry): bool
    {
        return (bool) ($this->urls->originalData($entry)['published'] ?? (bool) $entry->initialPath());
    }

    /**
     * Parse a stored date string into a Carbon at the precision it was stored.
     */
    private function parseDate(?string $date): ?Carbon
    {
        if (! $date) {
            return null;
        }

        $format = match (strlen($date)) {
            10 => 'Y-m-d',
            15 => 'Y-m-d-Hi',
            17 => 'Y-m-d-His',
        };

        $parsed = Carbon::createFromFormat($format, $date);

        return match ($format) {
            'Y-m-d' => $parsed->startOfDay(),
            'Y-m-d-Hi' => $parsed->startOfMinute(),
            'Y-m-d-His' => $parsed,
        };
    }

    /**
     * Redirect the entry from the path stashed on save.
     */
    private function redirectEntry(Entry $entry): void
    {
        $originalPath = $this->entries[$entry->id()] ?? null;

        unset($this->entries[$entry->id()]);

        $this->processEntryRedirect($entry, $originalPath);
    }

    /**
     * Delete automatic redirects that use the entry's current URL, and when
     * an original path was stashed, create the redirect from it.
     */
    private function processEntryRedirect(Entry $entry, ?string $originalPath = null): void
    {
        if (! $currentPath = $this->currentPath($entry)) {
            return;
        }

        if (! $this->isRedirectable($entry)) {
            return;
        }

        $this->redirects->deleteBySource($currentPath, $entry->locale());

        if (! $originalPath || $originalPath === $currentPath) {
            return;
        }

        $this->redirects->create($originalPath, "entry::{$entry->id()}", $entry->locale());
    }

    private function currentPath(Entry $entry): ?string
    {
        // A URI blinked before this change would hide the new path.
        Blink::store('entry-uris')->forget($entry->id());

        $url = $entry->absoluteUrlWithoutRedirect();

        return $url ? $entry->site()->relativePath($url) : null;
    }
}
