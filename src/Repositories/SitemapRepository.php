<?php

namespace Aerni\AdvancedSeo\Repositories;

use Aerni\AdvancedSeo\Sitemap\Sitemap;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Collection as EntriesCollection;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Taxonomy as TaxonomyFacade;

class SitemapRepository
{
    public function make(string $type, string $handle, string $site): Sitemap
    {
        return new Sitemap($type, $handle, $site);
    }

    public function find(string $type, string $handle, string $site): ?Sitemap
    {
        return $this->all()->first(function ($sitemap) use ($type, $handle, $site) {
            return $sitemap->type() === $type
                && $sitemap->handle() === $handle
                && $sitemap->site() === $site;
        });
    }

    public function all(): Collection
    {
        return $this->collectionSitemaps()->merge($this->taxonomySitemaps());
    }

    public function whereSite(string $site): Collection
    {
        return $this->all()->filter(function ($sitemap) use ($site) {
            return $sitemap->site() === $site;
        });
    }

    public function clearCache(): bool
    {
        $this->all()->each->clearCache();

        return true;
    }

    protected function collectionSitemaps(): Collection
    {
        return CollectionFacade::all()->flatMap(function ($collection) {
            return $collection->sites()->map(function ($site) use ($collection) {
                if ($this->collectionHasRoute($collection, $site)) {
                    return $this->make('collections', $collection->handle(), $site);
                }
            })->filter();
        });
    }

    protected function taxonomySitemaps(): Collection
    {
        return TaxonomyFacade::all()->flatMap(function ($taxonomy) {
            return $taxonomy->sites()->map(function ($site) use ($taxonomy) {
                if ($this->taxonomyHasRoute($taxonomy)) {
                    return $this->make('taxonomies', $taxonomy->handle(), $site);
                }
            });
        })->filter();
    }

    // TODO: Maybe we can remove this and check for the route in the Sitemap class instead.
    protected function collectionHasRoute(EntriesCollection $collection, string $site): bool
    {
        return ! is_null($collection->route($site));
    }

    // TODO: Maybe we can remove this altogether as we are checking for the views in the Sitemap class already.
    protected function taxonomyHasRoute(Taxonomy $taxonomy): bool
    {
        return $this->taxonomyRoutes($taxonomy)->isNotEmpty();
    }

    // TODO: Should this return false if not all routes exist? Or should we further distinguish between the type of page in the sitemap?
    // You can't configure routes per site like you can with collections.
    // So we just check the routes for the default site.
    protected function taxonomyRoutes(Taxonomy $taxonomy): Collection
    {
        $globalTaxonomyTemplate = $taxonomy->template();
        $globalTermTemplate = $taxonomy->queryTerms()->get()->first()->template();

        $collectionTaxonomies = $taxonomy->collections()->flatMap(function ($collection) use ($taxonomy) {
            return $collection->taxonomies()->map->collection($collection)->filter(function ($collectionTaxonomy) use ($taxonomy) {
                return $collectionTaxonomy->handle() === $taxonomy->handle();
            });
        });

        $collectionTaxonomyTemplates = $collectionTaxonomies->map->template();

        $collectionTermTemplates = $collectionTaxonomies->flatMap(function ($taxonomy) {
            return $taxonomy->queryTerms()->get()->map->collection($taxonomy->collection());
        })->map(fn ($term) =>  $term->template())->unique();

        $templates = collect($globalTaxonomyTemplate)
            ->merge($globalTermTemplate)
            ->merge($collectionTaxonomyTemplates)
            ->merge($collectionTermTemplates)
            ->filter(fn ($template) => view()->exists($template));

        return $templates;
    }
}
