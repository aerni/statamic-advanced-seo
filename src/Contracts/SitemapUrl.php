<?php

namespace Aerni\AdvancedSeo\Contracts;

interface SitemapUrl
{
    public function loc(): string|self;

    public function alternates(): array|self|null;

    public function lastmod(): string|self|null;

    public function changefreq(): string|self|null;

    public function priority(): string|self|null;

    public function site(): string|self;

    // TODO: This is only a temporary solution until the id() is implemented in all sitemaps.
    // public function id(): string|self;
}
