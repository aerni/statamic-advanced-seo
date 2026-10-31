<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Collection as StatamicCollection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\Stache;
use Statamic\Structures\CollectionTree;

class AffectedContent
{
    /** @var array<string, AffectedUrls> */
    private array $pending = [];

    public function __construct(private EntryUrlChange $urls) {}

    /** @return array<string, AffectedUrls> */
    public function captureEntry(Entry $entry): array
    {
        $changes = [];
        $changed = $this->urls->changedValues($entry);

        if (! $changed->isEmpty()) {
            $this->captureChildren($changes, $entry->id(), $entry);
        }

        if ($changed->data || $changed->date) {
            foreach ($entry->descendants() as $localization) {
                if (! $this->urls->inheritsChangedValues($entry, $localization)) {
                    continue;
                }

                $change = $this->change($changes, $localization->id());
                $change->localizations[] = ['entry' => $localization, 'path' => $this->originalPath($localization)];
                $this->captureMounts($changes, $localization->id(), $localization);
                $this->captureChildren($changes, $localization->id(), $localization);

                if ($changed->date && ! $localization->initialPath() && $this->urls->inheritsDate($entry, $localization)) {
                    $change->inheritedDates[] = $localization;
                }
            }
        }

        if (RedirectsFeature::enabled() && ! $changed->isEmpty()) {
            $this->captureMounts($changes, $entry->id(), $entry);
        }

        return $this->remember($changes);
    }

    /**
     * The tree already has its new shape, while stored URIs still describe the old one.
     *
     * @param  array<int, int|string>  $ids
     * @return array<string, AffectedUrls>
     */
    public function captureTree(array $ids): array
    {
        if (! RedirectsFeature::enabled() || ! $entry = EntryFacade::query()->whereIn('id', $ids)->first()) {
            return [];
        }

        $changes = [];
        $this->captureEntries($changes, "{$entry->collectionHandle()}::{$entry->locale()}", $ids);

        return $this->remember($changes);
    }

    /** @param array<string, AffectedUrls> $changes */
    public function prepareForSave(Entry $entry, array $changes): void
    {
        foreach ($changes as $change) {
            foreach ($change->inheritedDates as $localization) {
                // Eloquent copies the origin date; the localization must inherit its new value.
                $localization->date(null);
            }
        }

        $this->urls->forget($entry);
    }

    public function take(string $key): ?AffectedUrls
    {
        $change = $this->pending[$key] ?? null;
        unset($this->pending[$key]);

        return $change;
    }

    public function updateForSave(Entry $entry, AffectedUrls $change): void
    {
        if ($change->entries === []) {
            return;
        }

        $idsByCollection = $this->idsByCollection($change->entries);
        $ownIds = $idsByCollection[$entry->collectionHandle()] ?? [];
        unset($idsByCollection[$entry->collectionHandle()]);

        if ($ownIds !== []) {
            $this->rewriteChildPageUris($entry, $ownIds);
        }

        $this->rewriteCollections($idsByCollection);
    }

    public function updateForTree(CollectionTree $tree, AffectedUrls $change): void
    {
        Blink::store('entry-uris')->flush();
        Blink::store('structure-uris')->flush();

        $this->rewriteCollections($this->idsByCollection($change->entries, $tree->handle()));
    }

    /** @return Collection<int, Taxonomy> */
    public function taxonomiesForDeletion(Entry|StatamicCollection|Taxonomy $model): Collection
    {
        if ($model instanceof Entry) {
            return $this->collectionsAffectedBy($model)
                ->flatMap(fn (StatamicCollection $collection) => $collection->taxonomies()
                    ->map(fn (Taxonomy $taxonomy) => (clone $taxonomy)->collection($collection)));
        }

        if ($model instanceof StatamicCollection) {
            return $model->taxonomies()->map(fn (Taxonomy $taxonomy) => (clone $taxonomy)->collection($model));
        }

        return $model->collections()
            ->map(fn (StatamicCollection $collection) => (clone $model)->collection($collection))
            ->prepend((clone $model)->collection(null));
    }

    public function forgetDeletedPaths(Entry|StatamicCollection|Taxonomy $model): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        Blink::store('entry-uris')->flush();
        Blink::store('structure-uris')->flush();
        Blink::store('collection-mounts')->flush();

        if ($model instanceof StatamicCollection) {
            Blink::forget('collection-handles');
        }

        if ($model instanceof Entry) {
            $model->ancestors()->prepend($model)->each(function (Entry $entry) {
                Blink::forget('entry-descendants-'.$entry->id());
                Blink::forget('entry-all-descendants-'.$entry->id());
            });
        }
    }

    /**
     * @param  array<string, AffectedUrls>  $changes
     * @return array<string, AffectedUrls>
     */
    private function remember(array $changes): array
    {
        foreach ($changes as $change) {
            $this->pending[$change->key] ??= new AffectedUrls($change->key);
            $this->pending[$change->key]->entries += $change->entries;
        }

        return $changes;
    }

    /** @param array<string, AffectedUrls> $changes */
    private function change(array &$changes, string $key): AffectedUrls
    {
        return $changes[$key] ??= new AffectedUrls($key);
    }

    /** @param array<string, AffectedUrls> $changes */
    private function captureChildren(array &$changes, string $key, Entry $entry): void
    {
        if ($page = $entry->page()) {
            $this->captureEntries($changes, $key, $page->flattenedPages()->map->id()->all());
        }
    }

    /**
     * @param  array<string, AffectedUrls>  $changes
     * @param  array<int, Entry|int|string>  $entries
     */
    private function captureEntries(array &$changes, string $key, array $entries): void
    {
        $this->capturePaths($changes, $key, $entries);
        $visited = [];

        foreach ($entries as $entry) {
            if (! $entry instanceof Entry) {
                $entry = EntryFacade::find($entry);
            }

            if ($entry) {
                $this->captureMounts($changes, $key, $entry, $visited);
            }
        }
    }

    /**
     * @param  array<string, AffectedUrls>  $changes
     * @param  array<int, Entry|int|string>  $entries
     */
    private function capturePaths(array &$changes, string $key, array $entries): void
    {
        $this->change($changes, $key)->entries += $this->originalPaths($entries);
    }

    /**
     * @param  array<string, AffectedUrls>  $changes
     * @param  array<string, true>  $visited
     */
    private function captureMounts(array &$changes, string $key, Entry $mount, array &$visited = []): void
    {
        if (isset($visited[$mount->id()])) {
            return;
        }

        $visited[$mount->id()] = true;
        $collections = CollectionFacade::all()
            ->filter(fn (StatamicCollection $collection) => $collection->mount()?->in($mount->locale())?->id() === $mount->id());

        if ($collections->isEmpty()) {
            return;
        }

        $this->change($changes, $key)->mounts[] = [
            'site' => $mount->locale(),
            'collections' => $collections,
            'path' => $this->originalPath($mount),
        ];

        $entries = EntryFacade::query()
            ->whereIn('collection', $collections->map->handle()->all())
            ->where('site', $mount->locale())
            ->get()
            ->all();

        $this->capturePaths($changes, $key, $entries);

        foreach ($entries as $entry) {
            $this->captureMounts($changes, $key, $entry, $visited);
        }
    }

    /**
     * Stash the site-relative path still stored for each existing entry.
     * An id is looked up. An entry is used as given.
     *
     * @param  array<int, Entry|int|string>  $entries
     * @return array<string, string>
     */
    private function originalPaths(array $entries): array
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

    /** @return Collection<int, StatamicCollection> */
    private function collectionsAffectedBy(Entry $entry): Collection
    {
        $collections = CollectionFacade::all();
        $affected = [];

        do {
            $count = count($affected);

            foreach ($collections as $collection) {
                if (isset($affected[$collection->handle()])) {
                    continue;
                }

                $mount = $collection->mount()?->in($entry->locale());

                if ($mount && ((string) $mount->id() === (string) $entry->id() || isset($affected[$mount->collectionHandle()]))) {
                    $affected[$collection->handle()] = $collection;
                }
            }
        } while (count($affected) !== $count);

        return collect($affected)->values();
    }
}
