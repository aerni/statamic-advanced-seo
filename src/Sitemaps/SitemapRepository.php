<?php

namespace Aerni\AdvancedSeo\Sitemaps;

use Aerni\AdvancedSeo\Actions\IsEnabledModel;
use Aerni\AdvancedSeo\Contracts\Sitemap;
use Aerni\AdvancedSeo\Sitemaps\Collections\CollectionSitemap;
use Aerni\AdvancedSeo\Sitemaps\Custom\CustomSitemap;
use Aerni\AdvancedSeo\Sitemaps\Custom\CustomSitemapUrl;
use Aerni\AdvancedSeo\Sitemaps\Taxonomies\TaxonomySitemap;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Taxonomy;

class SitemapRepository
{
    protected array $customSitemaps = [];

    public function make(string $handle): CustomSitemap
    {
        return new CustomSitemap($handle);
    }

    public function makeUrl(string $loc): CustomSitemapUrl
    {
        return new CustomSitemapUrl($loc);
    }

    public function add(CustomSitemap $sitemap): void
    {
        $this->customSitemaps = $this->customSitemaps()
            ->push($sitemap)
            ->unique(fn ($sitemap) => $sitemap->handle())
            ->all();
    }

    public function all(): Collection
    {
        return $this->collectionSitemaps()
            ->merge($this->taxonomySitemaps())
            ->merge($this->customSitemaps());
    }

    public function find(string $id): ?Sitemap
    {
        $method = Str::before($id, '::').'Sitemaps';

        return $this->$method()->first(fn ($sitemap) => $id === $sitemap->id());
    }

    public function collectionSitemaps(): Collection
    {
        return CollectionFacade::all()
            ->filter(IsEnabledModel::handle(...))
            ->mapInto(CollectionSitemap::class)
            ->values();
    }

    public function taxonomySitemaps(): Collection
    {
        return Taxonomy::all()
            ->filter(IsEnabledModel::handle(...))
            ->mapInto(TaxonomySitemap::class)
            ->values();
    }

    public function customSitemaps(): Collection
    {
        return collect($this->customSitemaps);
    }

    public function clearCache(): void
    {
        $this->all()->each->clearCache();
    }

    public function cacheExpiry(): int
    {
        return config('advanced-seo.sitemap.expiry', 60) * 60;
    }
}
