<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Contracts\Sitemap;
use Illuminate\Support\Collection;

class SitemapRepository
{
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
        SitemapIndex::add($sitemap);
    }

    public function all(): Collection
    {
        return (new SitemapIndex)->sitemaps();
    }

    public function find(string $id): ?Sitemap
    {
        return $this->all()->first(fn ($sitemap) => $sitemap->id() === $id);
    }

    public function clearCache(): void
    {
        $this->all()->each->clearCache();
    }
}
