<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Sitemap\TaxonomySitemap;
use Aerni\AdvancedSeo\Sitemap\CollectionSitemap;
use Statamic\Facades\Taxonomy as TaxonomyFacade;
use Statamic\Facades\Collection as CollectionFacade;

class SitemapIndex
{
    protected static $customSitemaps = [];

    public static function add(CustomSitemap $sitemap): void
    {
        self::$customSitemaps = collect(self::$customSitemaps)
            ->push($sitemap)
            ->unique('handle')
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
            return $collection->sites()->map(fn ($site) => new CollectionSitemap($collection->handle(), $site));
        })->filter(fn ($sitemap) => $sitemap->indexable());
    }

    public function taxonomySitemaps(): Collection
    {
        return TaxonomyFacade::all()->flatMap(function ($taxonomy) {
            return $taxonomy->sites()->map(fn ($site) => new TaxonomySitemap($taxonomy->handle(), $site));
        })->filter(fn ($sitemap) => $sitemap->indexable());
    }

    public function customSitemaps(): Collection
    {
        return collect(self::$customSitemaps);
    }
}
