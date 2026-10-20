<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Collection as StatamicCollection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\Stache;
use Statamic\Facades\YAML;
use Statamic\Structures\CollectionTree;

class AutomaticEntryRedirects
{
    /**
     * Old path of each entry being saved, keyed by its id.
     * A localization stays here until its own save runs.
     *
     * @var array<string, string>
     */
    private array $entries = [];

    /**
     * Old paths of entries whose URL changed with a parent, mount, or tree.
     * Keyed by the saved entry id, or by the collection and site handles, such as "pages::german".
     *
     * @var array<string, array<string, string>>
     */
    private array $affectedEntries = [];

    public function __construct(
        private EntryUrlChange $urls,
        private AutomaticRedirectWriter $redirects,
    ) {}

    public function stash(Entry $entry): void
    {
        $this->stashMovedByOwnChange($entry);
        $this->stashMovedByParent($entry);
        $this->stashMovedByOrigin($entry);
        $this->stashMovedByMount($entry);

        // The next save() of this entry in this process has to read its own values.
        $this->urls->forget($entry);
    }

    /**
     * The tree is already the new shape, but stored URIs still describe the
     * previous one. Stash those paths and create the redirects once Statamic
     * has rewritten the moved pages on CollectionTreeSaved.
     *
     * @param  array<int, string>  $ids
     */
    public function stashMovedByTree(array $ids): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        if (! $entry = EntryFacade::query()->whereIn('id', $ids)->first()) {
            return;
        }

        $this->stashAffectedEntries("{$entry->collectionHandle()}::{$entry->locale()}", $ids);
    }

    public function create(Entry $entry): void
    {
        $this->redirectEntry($entry);
        $this->redirectMovedBySave($entry);
    }

    /**
     * Runs after Statamic's UpdateStructuredEntryUris listener, which is
     * registered earlier. Forget the URI caches anyway so a stale Blink
     * value from before the move can't hide the new path.
     */
    public function createMovedByTree(CollectionTree $tree): void
    {
        $key = "{$tree->handle()}::{$tree->locale()}";
        $paths = $this->affectedEntries[$key] ?? [];

        unset($this->affectedEntries[$key]);

        if ($paths === []) {
            return;
        }

        Blink::store('entry-uris')->flush();
        Blink::store('structure-uris')->flush();

        // The moved pages were already rewritten. Entries mounted on them were not.
        $this->rewriteCollections($this->idsByCollection($paths, $tree->handle()));

        $this->redirectAffected($paths);
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
    private function stashMovedByOwnChange(Entry $entry): void
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
     * A parent slug, date, or route field change moves every child page,
     * and every entry mounted on one of those pages.
     */
    private function stashMovedByParent(Entry $entry): void
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
     * Stash the stored path of each published child page and of the entries mounted on it.
     */
    private function stashChildPagePaths(Entry $entry): void
    {
        if (! $page = $entry->page()) {
            return;
        }

        $this->stashAffectedEntries($entry->id(), $page->flattenedPages()->map->id()->all());
    }

    /**
     * An origin route field or date is inherited by localizations that do not
     * set it. A localization route may use a field the origin route does not.
     * Entry::descendants() is that origin chain. Stash each live
     * localization's stored path, and its published child pages, before this
     * save rewrites them.
     */
    private function stashMovedByOrigin(Entry $entry): void
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
                $this->stashAffectedMounts($localization->id(), $localization);
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
     * Forget the entries affected by this save, then redirect them.
     */
    private function redirectMovedBySave(Entry $entry): void
    {
        $paths = $this->affectedEntries[$entry->id()] ?? [];

        unset($this->affectedEntries[$entry->id()]);

        if ($paths === []) {
            return;
        }

        $idsByCollection = $this->idsByCollection($paths);
        $ownIds = $idsByCollection[$entry->collectionHandle()] ?? [];

        unset($idsByCollection[$entry->collectionHandle()]);

        if ($ownIds !== []) {
            $this->rewriteChildPageUris($entry, $ownIds);
        }

        $this->rewriteCollections($idsByCollection);

        $this->redirectAffected($paths);
    }

    /**
     * @param  array<string, string>  $paths
     * @return array<string, array<int, string>>
     */
    private function idsByCollection(array $paths, ?string $exceptCollection = null): array
    {
        $idsByCollection = [];

        foreach (array_keys($paths) as $id) {
            $related = EntryFacade::find($id);

            if ($related->collectionHandle() === $exceptCollection) {
                continue;
            }

            $idsByCollection[$related->collectionHandle()][] = $id;
        }

        return $idsByCollection;
    }

    /**
     * Statamic rewrites the collection mounted on the saved entry, and the
     * pages in a tree that moved. Entries mounted on a child of that page, or
     * on a page that moved, stay on the old URI. The old URL would keep
     * serving them, so the redirect would never run.
     *
     * @param  array<string, array<int, string>>  $idsByCollection
     */
    private function rewriteCollections(array $idsByCollection): void
    {
        if ($idsByCollection === []) {
            return;
        }

        Blink::store('entry-uris')->flush();
        Blink::store('structure-uris')->flush();
        Blink::store('collection-mounts')->flush();

        foreach ($idsByCollection as $handle => $ids) {
            CollectionFacade::findByHandle($handle)->updateEntryUris($ids);
        }

        Blink::store('entry-uris')->flush();
    }

    /**
     * A localization's children and Eloquent entries keep the cached parent URI.
     * Put this parent in that cache so the rewrite uses the URI from this save.
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

    /**
     * The page was live and its slug, date, or a route field changed. A collection
     * mounted on it moves with the page, even when this page's own collection creates no redirect.
     */
    private function stashMovedByMount(Entry $entry): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        if (! $this->wasLive($entry)) {
            return;
        }

        if ($this->urls->changedValues($entry)->isEmpty()) {
            return;
        }

        $this->stashAffectedMounts($entry->id(), $entry);
    }

    /**
     * Store each entry's old path, plus the entries mounted on it.
     * Paths already stored for this save are left as they are.
     *
     * @param  array<int, Entry|string>  $entries
     */
    private function stashAffectedEntries(string $key, array $entries): void
    {
        $paths = $this->stashOriginalPaths($entries);

        foreach ($entries as $entry) {
            if (is_string($entry)) {
                $entry = EntryFacade::find($entry);
            }

            if (! $entry) {
                continue;
            }

            $paths += $this->mountedEntryPaths($entry);
        }

        $this->affectedEntries[$key] = ($this->affectedEntries[$key] ?? []) + $paths;
    }

    /**
     * Store the entries mounted on this page, not the page itself.
     */
    private function stashAffectedMounts(string $key, Entry $mount): void
    {
        $this->affectedEntries[$key] = ($this->affectedEntries[$key] ?? []) + $this->mountedEntryPaths($mount);
    }

    /**
     * @param  array<string, string>  $paths
     */
    private function redirectAffected(array $paths): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        foreach ($paths as $id => $originalPath) {
            $this->processEntryRedirect(EntryFacade::find($id), $originalPath);
        }
    }

    /**
     * Old paths of published entries in collections mounted on this page.
     *
     * @return array<string, string>
     */
    private function mountedEntryPaths(Entry $mount): array
    {
        $paths = [];

        foreach ($this->mountedCollections($mount) as $collection) {
            if (! $this->redirectsEnabled($collection)) {
                continue;
            }

            $ids = EntryFacade::query()
                ->where('collection', $collection->handle())
                ->where('site', $mount->locale())
                ->get()
                ->map->id()
                ->all();

            $paths += $this->stashOriginalPaths($ids);
        }

        return $paths;
    }

    private function redirectsEnabled(StatamicCollection $collection): bool
    {
        return (bool) Seo::find("collections::{$collection->handle()}")?->config()->value('redirects');
    }

    /**
     * Every collection mounted on this entry or its origin.
     */
    private function mountedCollections(Entry $entry): Collection
    {
        $ids = collect([$entry->id(), $entry->origin()?->id()])->filter();

        return CollectionFacade::all()
            ->filter(fn ($collection) => $ids->contains($collection->mount()?->id()))
            ->values();
    }

    private function currentPath(Entry $entry): ?string
    {
        // A URI blinked before this change would hide the new path.
        Blink::store('entry-uris')->forget($entry->id());

        $url = $entry->absoluteUrlWithoutRedirect();

        return $url ? $entry->site()->relativePath($url) : null;
    }
}
