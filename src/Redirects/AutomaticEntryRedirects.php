<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Concerns\ResolvesTermUrls;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Collection as StatamicCollection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\Stache;
use Statamic\Facades\Term as TermFacade;
use Statamic\Facades\URL;
use Statamic\Structures\CollectionTree;
use Statamic\Taxonomies\LocalizedTerm;

class AutomaticEntryRedirects
{
    use ResolvesTermUrls;

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

    /**
     * Old collection term paths, keyed the same way as $affectedEntries.
     *
     * @var array<string, list<array{term: string, site: string, collection: string, path: string}>>
     */
    private array $affectedCollectionTerms = [];

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
     * @param  array<int, int|string>  $ids
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
        $terms = $this->affectedCollectionTerms[$key] ?? [];

        unset($this->affectedEntries[$key], $this->affectedCollectionTerms[$key]);

        if ($paths === [] && $terms === []) {
            return;
        }

        Blink::store('entry-uris')->flush();
        Blink::store('structure-uris')->flush();

        // The moved pages were already rewritten. Entries mounted on them were not.
        $this->rewriteCollections($this->idsByCollection($paths, $tree->handle()));

        $this->redirectAffected($paths, $terms);
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
     * Stash stored paths for URI rewrites; redirect eligibility is checked after the save.
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
     * Entry::descendants() is that origin chain. Stash each live localization's
     * stored path before this save rewrites it. Child pages and mounted content
     * are checked independently of the localization's publication state.
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
            }

            $this->stashAffectedMounts($localization->id(), $localization);
            $this->stashChildPagePaths($localization);

            // Eloquent copies the origin date onto the localization. Clear that
            // copy so the save that follows writes the new date into the URI.
            // The old path was already read above.
            if ($changed->date && ! $localization->initialPath() && $this->urls->inheritsDate($entry, $localization)) {
                $localization->date(null);
            }
        }
    }

    /**
     * Stash the site-relative path still stored for each existing entry.
     * An id is looked up. An entry is used as given.
     *
     * @param  array<int, Entry|int|string>  $entries
     * @return array<string, string>
     */
    private function stashOriginalPaths(array $entries): array
    {
        $paths = [];

        foreach ($entries as $entry) {
            if (! $entry instanceof Entry) {
                $entry = EntryFacade::find($entry);
            }

            if (! $entry) {
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
     * Forget the entries and terms affected by this save, then redirect them.
     */
    private function redirectMovedBySave(Entry $entry): void
    {
        $paths = $this->affectedEntries[$entry->id()] ?? [];
        $terms = $this->affectedCollectionTerms[$entry->id()] ?? [];

        unset($this->affectedEntries[$entry->id()], $this->affectedCollectionTerms[$entry->id()]);

        if ($paths === [] && $terms === []) {
            return;
        }

        if ($paths !== []) {
            $idsByCollection = $this->idsByCollection($paths);
            $ownIds = $idsByCollection[$entry->collectionHandle()] ?? [];

            unset($idsByCollection[$entry->collectionHandle()]);

            if ($ownIds !== []) {
                $this->rewriteChildPageUris($entry, $ownIds);
            }

            $this->rewriteCollections($idsByCollection);
        }

        $this->redirectAffected($paths, $terms);
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
            Blink::store('entry-uris')->flush();
            Blink::store('collection-mounts')->flush();
        }
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
     * A collection mounted on this page moves when its slug, date, or a route
     * field changes. Mounted content is checked independently of the page's publication state.
     */
    private function stashMovedByMount(Entry $entry): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        if ($this->urls->changedValues($entry)->isEmpty()) {
            return;
        }

        $this->stashAffectedMounts($entry->id(), $entry);
    }

    /**
     * Store each entry's old path, plus the entries and terms mounted on it.
     * Paths already stored for this save are left as they are.
     *
     * @param  array<int, Entry|int|string>  $entries
     */
    private function stashAffectedEntries(string $key, array $entries): void
    {
        $this->stashAffectedEntryPaths($key, $entries);
        $visited = [];

        foreach ($entries as $entry) {
            if (! $entry instanceof Entry) {
                $entry = EntryFacade::find($entry);
            }

            if (! $entry) {
                continue;
            }

            $this->stashAffectedMounts($key, $entry, $visited);
        }
    }

    /**
     * Keep the first stored path for each affected entry.
     *
     * @param  array<int, Entry|int|string>  $entries
     */
    private function stashAffectedEntryPaths(string $key, array $entries): void
    {
        foreach ($this->stashOriginalPaths($entries) as $id => $path) {
            data_fill($this->affectedEntries, [$key, $id], $path);
        }
    }

    /**
     * Follow mounted collections once per entry, including mounts on another mounted entry.
     *
     * @param  array<string, true>  $visited
     */
    private function stashAffectedMounts(string $key, Entry $mount, array &$visited = []): void
    {
        if (isset($visited[$mount->id()])) {
            return;
        }

        $visited[$mount->id()] = true;
        $collections = $this->mountedCollections($mount);

        if ($collections->isEmpty()) {
            return;
        }

        $this->stashAffectedCollectionTerms($key, $mount, $collections);

        $entries = EntryFacade::query()
            ->whereIn('collection', $collections->map->handle()->all())
            ->where('site', $mount->locale())
            ->get()
            ->all();

        $this->stashAffectedEntryPaths($key, $entries);

        foreach ($entries as $entry) {
            $this->stashAffectedMounts($key, $entry, $visited);
        }
    }

    /**
     * @param  array<string, string>  $paths
     * @param  list<array{term: string, site: string, collection: string, path: string}>  $terms
     */
    private function redirectAffected(array $paths, array $terms): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        foreach ($paths as $id => $originalPath) {
            $this->processEntryRedirect(EntryFacade::find($id), $originalPath);
        }

        foreach ($terms as $term) {
            $this->redirectAffectedCollectionTerm($term);
        }
    }

    /**
     * Old collection term paths for the collections mounted on this page.
     * A later stash in this save replaces the earlier path.
     *
     * @param  Collection<int, StatamicCollection>  $collections
     */
    private function stashAffectedCollectionTerms(string $key, Entry $mount, Collection $collections): void
    {
        $this->affectedCollectionTerms[$key] = collect($this->affectedCollectionTerms[$key] ?? [])
            ->concat($collections->flatMap(fn (StatamicCollection $collection) => $this->affectedCollectionTermPaths($mount, $collection)))
            ->keyBy(fn (array $term) => "{$term['site']}|{$term['collection']}|{$term['term']}")
            ->values()
            ->all();
    }

    /**
     * @param  array{term: string, site: string, collection: string, path: string}  $stashed
     */
    private function redirectAffectedCollectionTerm(array $stashed): void
    {
        $localization = TermFacade::find($stashed['term'])->in($stashed['site']);
        $collection = CollectionFacade::findByHandle($stashed['collection']);

        $currentPath = $this->collectionTermPath($localization, $collection);

        if (! $currentPath || $currentPath === $stashed['path']) {
            return;
        }

        $this->redirects->deleteBySource($currentPath, $stashed['site']);
        $this->redirects->create($stashed['path'], $currentPath, $stashed['site']);
    }

    /**
     * Old collection term paths. The mount entry already resolves to the new
     * URL, so the old path is built from the URI still stored for it.
     *
     * @return Collection<int, array{term: string, site: string, collection: string, path: string}>
     */
    private function affectedCollectionTermPaths(Entry $mount, StatamicCollection $collection): Collection
    {
        $mountPath = $this->originalPath($mount);

        if (! $mountPath) {
            return collect();
        }

        return $collection->taxonomies()
            ->filter(fn (Taxonomy $taxonomy) => $this->redirectsEnabled($taxonomy))
            ->flatMap(function (Taxonomy $taxonomy) use ($collection, $mount, $mountPath) {
                $suffix = '/'.str_replace('_', '-', $taxonomy->handle());

                return $taxonomy->queryTerms()->get()
                    ->map(fn ($term) => $term->in($mount->locale()))
                    ->filter(fn (?LocalizedTerm $localized) => $localized && $this->collectionTermPath($localized, $collection) !== null)
                    ->map(fn (LocalizedTerm $localized) => [
                        'term' => $localized->id(),
                        'site' => $mount->locale(),
                        'collection' => $collection->handle(),
                        'path' => URL::tidy(url: $mountPath.$suffix.'/'.$localized->slug(), withTrailingSlash: false),
                    ]);
            });
    }

    private function redirectsEnabled(StatamicCollection|Taxonomy $model): bool
    {
        $type = $model instanceof StatamicCollection ? 'collections' : 'taxonomies';

        return (bool) Seo::find("{$type}::{$model->handle()}")?->config()->value('redirects');
    }

    /**
     * Every collection whose mount resolves to this entry on its site.
     */
    private function mountedCollections(Entry $entry): Collection
    {
        return CollectionFacade::all()
            ->filter(fn (StatamicCollection $collection) => $collection->mount()?->in($entry->locale())?->id() === $entry->id())
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
