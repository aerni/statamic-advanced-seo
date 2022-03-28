<?php

namespace Aerni\AdvancedSeo\Contracts;

use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Contracts\Taxonomies\Taxonomy;

interface Sitemap
{
    public function urls(): Collection|self;

    public static function make($model): static;

    public function handle(): string;

    public function type(): string;

    public function id(): string;

    public function url(): string;

    public function lastmod(): ?string;

    public function indexable(Entry|Term|Taxonomy $model, string $locale = null): bool;

    public function clearCache(): void;
}
