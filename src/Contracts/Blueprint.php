<?php

namespace Aerni\AdvancedSeo\Contracts;

use Aerni\AdvancedSeo\Data\DefaultsData;
use Statamic\Fields\Blueprint as BlueprintFields;

interface Blueprint
{
    public static function make(): self;

    public function data(DefaultsData $data): self;

    public function get(): BlueprintFields;

    public function items(): array;
}
