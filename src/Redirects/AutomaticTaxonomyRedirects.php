<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Concerns\ResolvesTaxonomyUrls;
use Aerni\AdvancedSeo\Concerns\ResolvesTermUrls;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Collection as StatamicCollection;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Taxonomy as TaxonomyFacade;
use Statamic\Facades\Term as TermFacade;

class AutomaticTaxonomyRedirects
{
    use ResolvesTaxonomyUrls;
    use ResolvesTermUrls;

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
        Blink::store('entry-uris')->flush();
        Blink::store('structure-uris')->flush();
        Blink::store('collection-mounts')->flush();

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
    public function stashDeletion(StatamicCollection|Taxonomy $model): void
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        $scopes = $model instanceof StatamicCollection
            ? $model->taxonomies()->map(fn (Taxonomy $taxonomy) => (clone $taxonomy)->collection($model))
            : $model->collections()
                ->map(fn (StatamicCollection $collection) => (clone $model)->collection($collection))
                ->prepend((clone $model)->collection(null));

        $paths = [];

        foreach ($scopes as $scope) {
            $terms = $model instanceof StatamicCollection
                ? (clone $scope)->collection(null)->queryTerms()->get()
                : collect();

            foreach ($scope->sites() as $site) {
                $paths[] = [
                    'site' => $site,
                    'path' => $this->taxonomyIndexPath($scope, $site, onlyPublicUrls: false),
                ];

                foreach ($terms as $term) {
                    $localized = $term->in($site);

                    if ($localized && ($path = (clone $localized)->collection($model)->uri())) {
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

    public function delete(StatamicCollection|Taxonomy $model): void
    {
        $key = $this->deletionKey($model);
        $paths = $this->deleting[$key] ?? [];
        unset($this->deleting[$key]);

        if (! RedirectsFeature::enabled()) {
            return;
        }

        foreach ($paths as $path) {
            if ($this->taxonomyIndexOwnsPath($path['path'], $path['site'])) {
                continue;
            }

            $term = TermFacade::findByUri($path['path'], $path['site']);

            if ($term && $this->termIsPublic($term) && $term->uri() === $path['path']) {
                continue;
            }

            $this->redirects->deleteByDestination($path['path'], $path['site']);
        }
    }

    private function deletionKey(StatamicCollection|Taxonomy $model): string
    {
        $type = $model instanceof StatamicCollection ? 'collection' : 'taxonomy';

        return "{$type}|{$model->handle()}";
    }

    private function redirectsEnabled(Taxonomy $taxonomy): bool
    {
        return (bool) Seo::find("taxonomies::{$taxonomy->handle()}")?->config()->value('redirects');
    }
}
