<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Contracts\Sitemap;
use Illuminate\Support\Collection;

class SitemapRepository
{
    public function make(string $handle, string $site, array $urls): CustomSitemap
    {
        return new CustomSitemap($handle, $site, $urls);
    }

    public static function makeItem(string $loc, ?string $lastmod = null, ?string $changefreq = null, ?string $priority = null): CustomSitemapItem
    {
        return new CustomSitemapItem($loc, $lastmod, $changefreq, $priority);
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

    public function clearCache(): bool
    {
        $this->all()->each->clearCache();

        return true;
    }
}
