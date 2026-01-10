<?php

namespace Aerni\AdvancedSeo\Contracts;

use Statamic\Fields\Blueprint as BlueprintFields;

interface Blueprint
{
    public static function make(): self;

    public static function resolve(mixed $model = null): BlueprintFields;

    public function for(mixed $model): self;

    public function get(): BlueprintFields;

    public function items(): array;
}
