<?php

namespace Aerni\AdvancedSeo\Repositories;

use Aerni\AdvancedSeo\Contracts\Sitemap;
use Aerni\AdvancedSeo\Sitemap\CustomSitemap;
use Aerni\AdvancedSeo\Sitemap\SitemapIndex;
use Illuminate\Support\Collection;

class SitemapRepository
{
    public function make(string $handle, string $site, array $items): CustomSitemap
    {
        return new CustomSitemap($handle, $site, $items);
    }

    public function add(CustomSitemap $sitemap): void
    {
        SitemapIndex::add($sitemap);
    }

    public function all(): Collection
    {
        return (new SitemapIndex)->items();
    }

    public function find(string $id): ?Sitemap
    {
        return $this->all()->first(fn ($sitemap) => $sitemap->id() === $id);
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
}
