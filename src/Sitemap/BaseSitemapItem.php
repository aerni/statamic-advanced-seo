<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Contracts\SitemapItem;

abstract class BaseSitemapItem implements SitemapItem
{
    abstract public function loc(): string|self;

    abstract public function alternates(): array|self|null;

    abstract public function lastmod(): string|self|null;

    abstract public function changefreq(): string|self|null;

    abstract public function priority(): string|self|null;

    public function toArray(): array
    {
        return [
            'loc' => $this->loc(),
            'alternates' => $this->alternates(),
            'lastmod' => $this->lastmod(),
            'changefreq' => $this->changefreq(),
            'priority' => $this->priority(),
        ];
    }
}
