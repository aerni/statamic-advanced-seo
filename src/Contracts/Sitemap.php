<?php

namespace Aerni\AdvancedSeo\Contracts;

use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;

interface Sitemap
{
    public function urls(): Collection;

    public function handle(): string;

    public function type(): string;

    public function id(): string;

    public function url(): string;

    public function lastmod(): ?string;

    public function indexable(Entry|Term|Taxonomy $model, string $locale = null): bool;

    public function clearCache(): void;
}
