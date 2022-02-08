<?php

namespace Aerni\AdvancedSeo\Contracts;

use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;

interface Fields
{
    public static function make(): self;

    public function data(Entry|Term|Collection $data): self;

    public function get(): array;

    public function items(): array;
}
