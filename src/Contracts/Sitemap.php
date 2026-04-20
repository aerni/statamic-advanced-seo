<?php

namespace Aerni\AdvancedSeo\Contracts;

use Illuminate\Support\Collection;

interface Sitemap
{
    public function urls(): Collection;

    public function handle(): string;

    public function type(): string;

    public function id(): string;

    public function url(): string;

    public function lastmod(): ?string;

    public function index(?SitemapIndex $index = null): self|SitemapIndex|null;

    public function save(): self;
}
