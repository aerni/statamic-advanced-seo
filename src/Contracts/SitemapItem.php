<?php

namespace Aerni\AdvancedSeo\Contracts;

interface SitemapItem
{
    public function loc(): string;

    public function lastmod(): ?string;

    public function changefreq(): ?string;

    public function priority(): ?string;

    public function toArray(): array;
}
