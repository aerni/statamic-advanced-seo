<?php

namespace Aerni\AdvancedSeo\Repositories;

use Statamic\Facades\Site;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Sitemap\Sitemap;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Facades\Taxonomy as TaxonomyFacade;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Contracts\Entries\Collection as EntriesCollection;

class SitemapRepository
{
    public function make(string $type, string $handle): Sitemap
    {
        return new Sitemap($type, $handle);
    }

    public function find(string $type, string $handle): ?Sitemap
    {
        return $this->all()->first(function ($sitemap) use ($type, $handle) {
            return $sitemap->type() === $type && $sitemap->handle() === $handle;
        });
    }

    public function all(): Collection
    {
        return $this->collectionSitemaps()->merge($this->taxonomySitemaps());
    }

    public function clearCache(string $site, string $type, string $handle): void
    {
        $this->find($type, $handle)->clearCache($site);
    }

    protected function collectionSitemaps(): Collection
    {
        return CollectionFacade::all()
            ->filter(function ($collection) {
                return $this->hasRoute($collection) && ! $this->excludeFromSitemap($collection);
            })->map(function ($collection) {
                return $this->make('collections', $collection->handle());
            });
    }

    protected function taxonomySitemaps(): Collection
    {
        return TaxonomyFacade::all()
            ->filter(function ($taxonomy) {
                return $this->hasRoute($taxonomy) && ! $this->excludeFromSitemap($taxonomy);
            })->map(function ($taxonomy) {
                return $this->make('taxonomies', $taxonomy->handle());
            });
    }

    protected function hasRoute(EntriesCollection|Taxonomy $data): bool
    {
        return $data instanceof EntriesCollection
            ? ! is_null($data->route(Site::current()->handle()))
            : $this->taxonomyRoutes($data)->isNotEmpty();
    }

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

    protected function excludeFromSitemap(EntriesCollection|Taxonomy $data): bool
    {
        $config = $this->config();

        $excluded = $data instanceof EntriesCollection
            ? $config->get('excluded_collections') ?? []
            : $config->get('excluded_taxonomies') ?? [];

        return in_array($data->handle(), $excluded);
    }

    protected function config(): SeoVariables
    {
        return Seo::findOrMake('site', 'sitemap')
            // ->createLocalizations(Site::all()->map->handle()) // TODO: Only create if it doesn't exist. See Tinkerwell error.
            ->in(Site::current());
    }
}
