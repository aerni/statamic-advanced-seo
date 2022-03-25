<?php

namespace Aerni\AdvancedSeo\Contracts;

use Illuminate\Support\Collection;

interface Sitemap
{
    public function items(): Collection|self;

    public function id(): string;

    public function url(): string;

    public function lastmod(): ?string;

    public function clearCache(): void;

    public function indexable(): bool;
}
