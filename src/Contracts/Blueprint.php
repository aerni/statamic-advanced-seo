<?php

namespace Aerni\AdvancedSeo\Contracts;

use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Fields\Blueprint as BlueprintFields;

interface Blueprint
{
    public static function make(): self;

    public function data(Entry|Term|Collection $data): self;

    public function get(): BlueprintFields;

    public function items(): array;
}
