<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Statamic\Statamic;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Contracts\Sitemap;
use Statamic\Facades\Taxonomy as TaxonomyApi;
use Statamic\Facades\Collection as CollectionApi;

class SitemapRepository
{
    protected array $customSitemaps = [];

    public function make(string $handle): CustomSitemap
    {
        return new CustomSitemap($handle);
    }

    public static function makeUrl(string $loc): CustomSitemapUrl
    {
        return new CustomSitemapUrl($loc);
    }

    public function add(CustomSitemap $sitemap): void
    {
        $this->customSitemaps = $this->customSitemaps()
            ->push($sitemap)
            ->unique(fn ($sitemap) => $sitemap->handle())
            ->toArray();
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
        return CollectionApi::all()
            ->map(fn ($collection) => new CollectionSitemap($collection))
            ->filter(fn ($sitemap) => $sitemap->urls()->isNotEmpty());
    }

    public function taxonomySitemaps(): Collection
    {
        return TaxonomyApi::all()
            ->map(fn ($taxonomy) => new TaxonomySitemap($taxonomy))
            ->filter(fn ($sitemap) => $sitemap->urls()->isNotEmpty());
    }

    public function customSitemaps(): Collection
    {
        return collect($this->customSitemaps);
    }

    public function clearCache(): void
    {
        $this->all()->each->clearCache();
    }

    public function refreshCache(): void
    {
        $this->all()->each->refreshCache();
    }
}
