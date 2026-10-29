<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Concerns\ResolvesTaxonomyUrls;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Collection as StatamicCollection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Taxonomy as TaxonomyFacade;

class AutomaticTaxonomyRedirects
{
    use ResolvesTaxonomyUrls;

    /** @var array<string, array<string, array{taxonomy: string, site: string, collection: string, path: string}>> */
    private array $paths = [];

    /** @var array<string, list<array{site: string, path: string}>> */
    private array $deleting = [];

    public function __construct(private AutomaticRedirectWriter $redirects) {}

    /**
     * Capture public indexes of mounted collections, including empty ones.
     *
     * @param  Collection<int, StatamicCollection>  $collections
     */
    public function stash(string $key, string $site, Collection $collections, ?string $mountPath): void
    {
        if (! RedirectsFeature::enabled() || $mountPath === null) {
            return;
        }

        foreach ($collections as $collection) {
            foreach ($collection->taxonomies() as $taxonomy) {
                if (! $this->redirectsEnabled($taxonomy)) {
                    continue;
                }

                $scoped = (clone $taxonomy)->collection($collection);

                if ($path = $this->taxonomyIndexPath($scoped, $site, $mountPath)) {
                    $this->paths[$key]["{$site}|{$collection->handle()}|{$taxonomy->handle()}"] ??= [
                        'taxonomy' => $taxonomy->handle(),
                        'site' => $site,
                        'collection' => $collection->handle(),
                        'path' => $path,
                    ];
                }
            }
        }
    }

    public function hasStashedPaths(string $key): bool
    {
        return ! empty($this->paths[$key]);
    }

    public function create(string $key): void
    {
        $paths = $this->paths[$key] ?? [];
        unset($this->paths[$key]);

        if ($paths === [] || ! RedirectsFeature::enabled()) {
            return;
        }

        // Empty collections trigger no entry URI rewrite to clear these caches.
        $this->forgetPathCaches();

        foreach ($paths as $stashed) {
            $taxonomy = TaxonomyFacade::find($stashed['taxonomy']);
            $collection = CollectionFacade::findByHandle($stashed['collection']);

            if (! $taxonomy || ! $collection || ! $this->redirectsEnabled($taxonomy)) {
                continue;
            }

            $scoped = (clone $taxonomy)->collection($collection);

            if (! $currentPath = $this->taxonomyIndexPath($scoped, $stashed['site'])) {
                continue;
            }

            $this->redirects->deleteBySource($currentPath, $stashed['site']);

            if ($stashed['path'] !== $currentPath) {
                $this->redirects->create($stashed['path'], $currentPath, $stashed['site']);
            }
        }
    }

    /**
     * Keep paths before their collection associations disappear. Collection
     * deletion removes scoped term pages as well as taxonomy index pages.
     */
    public function stashDeletion(Entry|StatamicCollection|Taxonomy $model): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        $scopes = $this->deletionScopes($model);

        $paths = [];

        foreach ($scopes as $scope) {
            $terms = $model instanceof Taxonomy
                ? collect()
                : (clone $scope)->collection(null)->queryTerms()->get();
            $sites = $model instanceof Entry ? collect([$model->locale()]) : $scope->sites();

            foreach ($sites as $site) {
                $paths[] = [
                    'site' => $site,
                    'path' => $this->taxonomyIndexPath($scope, $site, onlyPublicUrls: false),
                ];

                foreach ($terms as $term) {
                    $localized = $term->in($site);

                    if ($localized && ($path = (clone $localized)->collection($scope->collection())->uri())) {
                        $paths[] = ['site' => $site, 'path' => $path];
                    }
                }
            }
        }

        $this->deleting[$this->deletionKey($model)] = collect($paths)
            ->unique(fn (array $path) => "{$path['site']}|{$path['path']}")
            ->values()
            ->all();
    }

    public function delete(Entry|StatamicCollection|Taxonomy $model): void
    {
        $key = $this->deletionKey($model);
        $paths = $this->deleting[$key] ?? [];
        unset($this->deleting[$key]);

        if (! RedirectsFeature::enabled()) {
            return;
        }

        $this->forgetPathCaches();

        if ($model instanceof StatamicCollection) {
            Blink::forget('collection-handles');
        }

        if ($model instanceof Entry) {
            $model->ancestors()->prepend($model)->each(function (Entry $entry) {
                Blink::forget('entry-descendants-'.$entry->id());
                Blink::forget('entry-all-descendants-'.$entry->id());
            });
        }

        foreach ($paths as $path) {
            if (! $this->redirects->hasPublicOwner($path['path'], $path['site'])) {
                $this->redirects->deleteByDestination($path['path'], $path['site']);
            }
        }
    }

    /** @return Collection<int, Taxonomy> */
    private function deletionScopes(Entry|StatamicCollection|Taxonomy $model): Collection
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

    private function deletionKey(Entry|StatamicCollection|Taxonomy $model): string
    {
        $type = match (true) {
            $model instanceof Entry => 'entry',
            $model instanceof StatamicCollection => 'collection',
            default => 'taxonomy',
        };

        return "{$type}|{$model->id()}";
    }

    private function forgetPathCaches(): void
    {
        Blink::store('entry-uris')->flush();
        Blink::store('structure-uris')->flush();
        Blink::store('collection-mounts')->flush();
    }

    private function redirectsEnabled(Taxonomy $taxonomy): bool
    {
        return (bool) Seo::find("taxonomies::{$taxonomy->handle()}")?->config()->value('redirects');
    }
}
