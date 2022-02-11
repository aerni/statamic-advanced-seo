<?php

namespace Aerni\AdvancedSeo\Repositories;

use Aerni\AdvancedSeo\Contracts\Sitemap;
use Aerni\AdvancedSeo\Sitemap\CollectionSitemap;
use Aerni\AdvancedSeo\Sitemap\TaxonomySitemap;
use Illuminate\Support\Collection;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Taxonomy as TaxonomyFacade;

class SitemapRepository
{
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
        return $this->all()->filter(fn ($sitemap) => $sitemap->site() === $site);
    }

    public function clearCache(): bool
    {
        $this->all()->each->clearCache();

        return true;
    }

    protected function collectionSitemaps(): Collection
    {
        return CollectionFacade::all()->flatMap(function ($collection) {
            return $collection->sites()->map(fn ($site) => CollectionSitemap::make($collection->handle(), $site));
        })->filter(fn ($sitemap) => $sitemap->indexable());
    }

    protected function taxonomySitemaps(): Collection
    {
        return TaxonomyFacade::all()->flatMap(function ($taxonomy) {
            return $taxonomy->sites()->map(fn ($site) => TaxonomySitemap::make($taxonomy->handle(), $site));
        })->filter(fn ($sitemap) => $sitemap->indexable());
    }
}
