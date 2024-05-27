<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Contracts\Sitemap;
use Illuminate\Support\Collection;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Taxonomy as TaxonomyFacade;

class SitemapIndex
{
    protected static array $customSitemaps = [];

    public static function add(CustomSitemap $sitemap): void
    {
        self::$customSitemaps = collect(self::$customSitemaps)
            ->push($sitemap)
            ->unique(fn ($sitemap) => $sitemap->handle())
            ->toArray();
    }

    public function sitemaps(): Collection
    {
        return $this->collectionSitemaps()
            ->merge($this->taxonomySitemaps())
            ->merge($this->customSitemaps())
            ->filter(fn (Sitemap $sitemap) => $sitemap->urls()->isNotEmpty());
    }

    public function collectionSitemaps(): Collection
    {
        return CollectionFacade::all()->map(fn ($collection) => new CollectionSitemap($collection));
    }

    public function taxonomySitemaps(): Collection
    {
        return TaxonomyFacade::all()->map(fn ($taxonomy) => new TaxonomySitemap($taxonomy));
    }

    public function customSitemaps(): Collection
    {
        return collect(self::$customSitemaps);
    }
}
