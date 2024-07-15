<?php

namespace Aerni\AdvancedSeo\Sitemaps;

use Aerni\AdvancedSeo\Actions\IsEnabledModel;
use Illuminate\Support\Collection;
use Statamic\Facades\Taxonomy as TaxonomyFacade;
use Aerni\AdvancedSeo\Sitemaps\Custom\CustomSitemap;
use Statamic\Facades\Collection as CollectionFacade;
use Aerni\AdvancedSeo\Sitemaps\Taxonomies\TaxonomySitemap;
use Aerni\AdvancedSeo\Sitemaps\Collections\CollectionSitemap;

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
            ->merge($this->customSitemaps());
    }

    public function collectionSitemaps(): Collection
    {
        return CollectionFacade::all()
            ->filter(IsEnabledModel::handle(...))
            ->map(fn ($collection) => new CollectionSitemap($collection))
            ->values();
    }

    public function taxonomySitemaps(): Collection
    {
        return TaxonomyFacade::all()
            ->filter(IsEnabledModel::handle(...))
            ->map(fn ($taxonomy) => new TaxonomySitemap($taxonomy))
            ->values();
    }

    public function customSitemaps(): Collection
    {
        return collect(self::$customSitemaps);
    }
}
