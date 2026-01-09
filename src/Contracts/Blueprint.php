<?php

namespace Aerni\AdvancedSeo\Contracts;

use Aerni\AdvancedSeo\Context\Context;
use Statamic\Fields\Blueprint as BlueprintFields;

interface Blueprint
{
    public static function make(): self;

    public function context(Context $context): self;

    public function get(): BlueprintFields;

    public function items(): array;
}
