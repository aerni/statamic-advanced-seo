<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Contracts\SitemapItem;

abstract class BaseSitemapItem implements SitemapItem
{
    abstract public function loc(): string;

    abstract public function lastmod(): ?string;

    abstract public function changefreq(): ?string;

    abstract public function priority(): ?string;

    public function toArray(): array
    {
        return [
            'loc' => $this->loc(),
            'lastmod' => $this->lastmod(),
            'changefreq' => $this->changefreq(),
            'priority' => $this->priority(),
        ];
    }
}
