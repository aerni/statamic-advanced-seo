<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Carbon\Carbon;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Blink;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\Stache;
use Statamic\Facades\YAML;
use Statamic\Structures\CollectionTree;

class AutomaticEntryRedirects
{
    /** @var array<string, string> */
    private array $entries = [];

    /**
     * Stored paths of structure child pages, keyed by the parent entry id.
     *
     * @var array<string, array<string, string>>
     */
    private array $childPages = [];

    /**
     * Stored paths of moved entries, keyed by collection and site.
     *
     * @var array<string, array<string, string>>
     */
    private array $movedEntries = [];

    public function __construct(
        private EntryUrlChange $urls,
        private AutomaticRedirectWriter $redirects,
    ) {}

    public function stash(Entry $entry): void
    {
        $this->stashEntry($entry);
        $this->stashChildPages($entry);
        $this->stashEntryLocalizations($entry);

        // The next save() of this entry in this process has to read its own values.
        $this->urls->forget($entry);
    }

    /**
     * The tree is already the new shape, but stored URIs still describe the
     * previous one. Stash those paths and create the redirects once Statamic
     * has rewritten them on CollectionTreeSaved.
     *
     * @param  array<int, string>  $ids
     */
    public function stashMoved(array $ids): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        if (! $entry = EntryFacade::query()->whereIn('id', $ids)->first()) {
            return;
        }

        $this->movedEntries[$entry->collectionHandle().'::'.$entry->locale()] = $this->stashOriginalPaths($ids);
    }

    public function create(Entry $entry): void
    {
        $this->redirectEntry($entry);
        $this->redirectChildPages($entry);
    }

    /**
     * Runs after Statamic's UpdateStructuredEntryUris listener, which is
     * registered earlier. Forget the URI caches anyway so a stale Blink
     * value from before the move can't hide the new path.
     */
    public function createMoved(CollectionTree $tree): void
    {
        $key = $tree->handle().'::'.$tree->locale();
        $paths = $this->movedEntries[$key] ?? [];

        unset($this->movedEntries[$key]);

        if (empty($paths)) {
            return;
        }

        Blink::store('entry-uris')->flush();
        Blink::store('structure-uris')->flush();

        foreach ($paths as $id => $originalPath) {
            if (! $entry = EntryFacade::find($id)) {
                continue;
            }

            $this->processEntryRedirect($entry, $originalPath);
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
     * Stash this entry's original URL when its slug, date, or a route field
     * changed, it is live now, and it was live before this save.
     */
    private function stashEntry(Entry $entry): void
    {
        if (! $this->isRedirectable($entry)) {
            return;
        }

        if (! $this->wasLive($entry)) {
            return;
        }

        $changed = $this->urls->changedValues($entry);

        if ($changed->isEmpty()) {
            return;
        }

        if (! $originalUrl = $this->urls->originalUrl($entry, $changed)) {
            return;
        }

        $this->entries[$entry->id()] = $entry->site()->relativePath($originalUrl);
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
        // Flat file: the previous published flag is still in the file.
        // getOriginal() has already been replaced.
        if ($path = $entry->initialPath()) {
            return (bool) (YAML::file($path)->parse()['published'] ?? true);
        }

        // Eloquent: there is no file, so getOriginal() is still the pre-save value.
        return (bool) $entry->getOriginal('published');
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
     * A parent slug, date, or route field change moves every child page.
     * Stash the path still stored for each published one.
     */
    private function stashChildPages(Entry $entry): void
    {
        if (! $entry->page()) {
            return;
        }

        if ($this->urls->changedValues($entry)->isEmpty()) {
            return;
        }

        $this->stashChildPagePaths($entry);
    }

    /**
     * Stash the stored path of each published child page.
     */
    private function stashChildPagePaths(Entry $entry): void
    {
        if (! $page = $entry->page()) {
            return;
        }

        $this->childPages[$entry->id()] = $this->stashOriginalPaths(
            $page->flattenedPages()->map->id()->all(),
        );
    }

    /**
     * An origin route field or date is inherited by localizations that do not
     * set it. A localization route may use a field the origin route does not.
     * Entry::descendants() is that origin chain. Stash each live
     * localization's stored path, and its published child pages, before this
     * save rewrites them.
     */
    private function stashEntryLocalizations(Entry $entry): void
    {
        $changed = $this->urls->changedValues($entry);

        if (! $changed->data && ! $changed->date) {
            return;
        }

        foreach ($entry->descendants() as $localization) {
            if (! $this->urls->inheritsChangedValues($entry, $localization)) {
                continue;
            }

            if ($this->wasLive($localization)) {
                $this->entries += $this->stashOriginalPaths([$localization]);
            }

            $this->stashChildPagePaths($localization);

            // Eloquent copies the origin date onto the localization. Clear that
            // copy so the save that follows writes the new date into the URI.
            // The old path was already read above.
            if ($changed->date && ! $localization->initialPath() && $localization->hasExplicitDate()) {
                $localization->date(null);
            }
        }
    }

    /**
     * Stash the site-relative path still stored for each published entry.
     * An id is looked up. An entry is used as given.
     *
     * @param  array<int, Entry|string>  $entries
     * @return array<string, string>
     */
    private function stashOriginalPaths(array $entries): array
    {
        $paths = [];

        foreach ($entries as $entry) {
            if (is_string($entry)) {
                $entry = EntryFacade::find($entry);
            }

            if (! $entry) {
                continue;
            }

            if (! $this->isRedirectable($entry)) {
                continue;
            }

            if (! $path = $this->originalPath($entry)) {
                continue;
            }

            $paths[$entry->id()] = $path;
        }

        return $paths;
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
    private function originalPath(Entry $entry): ?string
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
            $uri = $entry->model()->getAttribute('uri');
        }

        return is_string($uri) && $uri !== '' ? $uri : null;
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
     * Statamic does not rewrite child page URIs on this save, so do that
     * before redirecting from the paths stashed above.
     */
    private function redirectChildPages(Entry $entry): void
    {
        $paths = $this->childPages[$entry->id()] ?? null;

        unset($this->childPages[$entry->id()]);

        if (! $paths) {
            return;
        }

        // TODO: Drop this call once Statamic rewrites child URIs on the parent save.
        $this->rewriteChildPageUris($entry, array_keys($paths));

        foreach ($paths as $id => $originalPath) {
            if (! $child = EntryFacade::find($id)) {
                continue;
            }

            $this->processEntryRedirect($child, $originalPath);
        }
    }

    /**
     * TODO: Statamic should have rewritten these URIs in updateChildPageUris()
     * before EntrySaved. It doesn't: Entry::save() syncOriginal()s the
     * cached entry, so shouldUpdateUris() is false. Drop this method once
     * that bug is fixed. Creating the redirects stays.
     *
     * @param  array<int, string>  $ids
     */
    private function rewriteChildPageUris(Entry $entry, array $ids): void
    {
        Blink::store('entry-uris')->flush();
        Blink::store('structure-uris')->flush();
        Blink::store('structure-entries')->flush();
        Blink::store('structure-entries')->put($entry->id(), $entry);

        $entry->collection()->updateEntryUris($ids);

        Blink::store('entry-uris')->flush();
        Blink::store('structure-uris')->flush();
    }

    /**
     * Delete automatic redirects that use the entry's current URL, and when
     * an original path was stashed, create the redirect from it.
     */
    private function processEntryRedirect(Entry $entry, ?string $originalPath = null): void
    {
        if (! $this->isRedirectable($entry)) {
            return;
        }

        if (! $currentPath = $this->currentPath($entry)) {
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
