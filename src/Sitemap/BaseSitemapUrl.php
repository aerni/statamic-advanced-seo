<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Contracts\SitemapUrl;

abstract class BaseSitemapUrl implements SitemapUrl
{
    abstract public function loc(): string|self;

    abstract public function alternates(): array|self|null;

    abstract public function lastmod(): string|self|null;

    abstract public function changefreq(): string|self|null;

    abstract public function priority(): string|self|null;
}
