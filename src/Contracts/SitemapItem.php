<?php

namespace Aerni\AdvancedSeo\Contracts;

interface SitemapItem
{
    public function loc(): string|self;

    public function lastmod(): string|self|null;

    public function changefreq(): string|self|null;

    public function priority(): string|self|null;

    public function toArray(): array;
}
