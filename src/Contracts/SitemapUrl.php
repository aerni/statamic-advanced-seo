<?php

namespace Aerni\AdvancedSeo\Contracts;

interface SitemapUrl
{
    public function loc(): string|self;

    public function alternates(): array|self|null;

    public function lastmod(): string|self|null;

    public function site(): string|self;

    public function sitemap(?Sitemap $sitemap = null): self|Sitemap;
}
