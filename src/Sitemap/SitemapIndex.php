<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Sitemap\CollectionSitemap;
use Aerni\AdvancedSeo\Sitemap\TaxonomySitemap;
use Illuminate\Support\Collection;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Taxonomy as TaxonomyFacade;

class SitemapIndex
{
    protected static $customSitemaps = [];

    public static function add(CustomSitemap $sitemap): void
    {
        self::$customSitemaps = collect(self::$customSitemaps)
            ->push($sitemap)
            ->unique(fn ($sitemap) => $sitemap->type.$sitemap->handle)
            ->toArray();
    }

    public function items(): Collection
    {
        return $this->collectionSitemaps()
            ->merge($this->taxonomySitemaps())
            ->merge($this->customSitemaps());
    }

    public function collectionSitemaps(): Collection
    {
        return CollectionFacade::all()->flatMap(function ($collection) {
            return $collection->sites()->map(fn ($site) => CollectionSitemap::make($collection->handle(), $site));
        })->filter(fn ($sitemap) => $sitemap->indexable());
    }

    public function taxonomySitemaps(): Collection
    {
        return TaxonomyFacade::all()->flatMap(function ($taxonomy) {
            return $taxonomy->sites()->map(fn ($site) => TaxonomySitemap::make($taxonomy->handle(), $site));
        })->filter(fn ($sitemap) => $sitemap->indexable());
    }

    public function customSitemaps(): Collection
    {
        return collect(self::$customSitemaps);
    }
}
