<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Illuminate\Support\Collection;
use Statamic\Facades\Collection as CollectionApi;
use Statamic\Facades\Taxonomy as TaxonomyApi;
use Aerni\AdvancedSeo\Contracts\Sitemap;

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
            ->merge($this->customSitemaps())
            ->filter(fn (Sitemap $sitemap) => $sitemap->urls()->isNotEmpty());
    }

    public function find(string $id): ?Sitemap
    {
        return $this->all()->first(fn ($sitemap) => $id === $sitemap->id());
    }

    public function collectionSitemaps(): Collection
    {
        return CollectionApi::all()->map(fn ($collection) => new CollectionSitemap($collection));
    }

    public function taxonomySitemaps(): Collection
    {
        return TaxonomyApi::all()->map(fn ($taxonomy) => new TaxonomySitemap($taxonomy));
    }

    public function customSitemaps(): Collection
    {
        return collect($this->customSitemaps);
    }

    public function clearCache(): void
    {
        $this->all()->each->clearCache();
    }
}
