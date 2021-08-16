<?php

namespace Aerni\AdvancedSeo\Contracts;

use Statamic\Fields\Blueprint as BlueprintFields;

interface Blueprint
{
    public static function make(): self;

    public function data($data): self;

    public function get(): BlueprintFields;
}
