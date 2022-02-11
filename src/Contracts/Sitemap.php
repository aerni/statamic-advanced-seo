<?php

namespace Aerni\AdvancedSeo\Contracts;

use Illuminate\Support\Collection;

interface Sitemap
{
    public static function make(string $handle, string $site): self;

    public function items(): Collection;

    public function type(): string;

    public function handle(): string;

    public function site(): string;

    public function url(): string;

    public function lastmod(): string;

    public function clearCache(): void;

    public function indexable(): bool;
}
