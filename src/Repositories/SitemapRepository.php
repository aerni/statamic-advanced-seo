<?php

namespace Aerni\AdvancedSeo\Repositories;

use Aerni\AdvancedSeo\Contracts\Sitemap;
use Aerni\AdvancedSeo\Sitemap\CustomSitemap;
use Aerni\AdvancedSeo\Sitemap\SitemapIndex;
use Illuminate\Support\Collection;

class SitemapRepository
{
    public function make(string $type, string $handle, string $site, array $items): CustomSitemap
    {
        return new CustomSitemap($type, $handle, $site, $items);
    }

    public function add(CustomSitemap $sitemap): void
    {
        SitemapIndex::add($sitemap);
    }

    public function find(string $type, string $handle, string $site): ?Sitemap
    {
        return $this->all()->first(function ($sitemap) use ($type, $handle, $site) {
            return $sitemap->type() === $type
                && $sitemap->handle() === $handle
                && $sitemap->site() === $site;
        });
    }

    public function all(): Collection
    {
        return (new SitemapIndex)->items();
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
