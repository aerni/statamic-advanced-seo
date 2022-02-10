<?php

namespace Aerni\AdvancedSeo\Repositories;

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Sitemap\Sitemap;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Collection as EntriesCollection;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Site;
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
        return CollectionFacade::all()
            ->flatMap(function ($collection) {
                return $collection->sites()->map(function ($site) use ($collection) {
                    if ($this->hasRoute($collection, $site) && ! $this->excludeFromSitemap($collection, $site)) {
                        return $this->make('collections', $collection->handle(), $site);
                    }
                })->filter();
            });
    }

    protected function taxonomySitemaps(): Collection
    {
        return TaxonomyFacade::all()
            ->flatMap(function ($taxonomy) {
                return $taxonomy->sites()->map(function ($site) use ($taxonomy) {
                    if ($this->hasRoute($taxonomy) && ! $this->excludeFromSitemap($taxonomy, $site)) {
                        return $this->make('taxonomies', $taxonomy->handle(), $site);
                    }
                })->filter();
            });
    }

    protected function hasRoute(EntriesCollection|Taxonomy $data, string $site = null): bool
    {
        return $data instanceof EntriesCollection
            ? ! is_null($data->route($site))
            : $this->taxonomyRoutes($data)->isNotEmpty();
    }

    // You can't configure routes per site like you can with collections.
    // So we just check the routes for the default site.
    protected function taxonomyRoutes(Taxonomy $taxonomy): Collection
    {
        $globalTaxonomyTemplates = $taxonomy->template();
        $globalTermTemplates = $taxonomy->queryTerms()->get()->map->template();

        $collectionTaxonomies = CollectionFacade::all()
            ->filter(function ($collection) {
                return ! $this->excludeFromSitemap($collection);
            })->flatMap(function ($collection) {
                return $collection->taxonomies()->map->collection($collection);
            })->filter(function ($taxonomy) {
                return ! $this->excludeFromSitemap($taxonomy);
            });

        $collectionTaxonomyTemplates = $collectionTaxonomies->map->template();

        $collectionTermTemplates = $collectionTaxonomies->flatMap(function ($taxonomy) {
            return $taxonomy->queryTerms()->get()->map->collection($taxonomy->collection());
        })->map(function ($term) {
            return $term->template();
        });

        $templateViews = collect($globalTaxonomyTemplates)
            ->merge($globalTermTemplates)
            ->merge($collectionTaxonomyTemplates)
            ->merge($collectionTermTemplates)
            ->map(function ($template) {
                return view()->exists($template);
            })->filter();

        return $templateViews;
    }

    protected function excludeFromSitemap(EntriesCollection|Taxonomy $data, string $site = null): bool
    {
        $site = Site::get($site) ?? Site::current();

        $config = Seo::find('site', 'indexing')
            ?->createLocalizations(Site::all()->map->handle())
            ->in($site);

        $excluded = $data instanceof EntriesCollection
            ? $config->value('excluded_collections') ?? []
            : $config->value('excluded_taxonomies') ?? [];

        return in_array($data->handle(), $excluded);
    }
}
