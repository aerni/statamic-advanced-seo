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

    public function canonicalTypeIsCurrent(): bool;
}
