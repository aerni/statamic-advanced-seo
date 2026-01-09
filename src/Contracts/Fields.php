<?php

namespace Aerni\AdvancedSeo\Contracts;

use Aerni\AdvancedSeo\Context\Context;

interface Fields
{
    public static function make(): self;

    public function context(Context $context): self;

    public function get(): array;

    public function items(): array;
}
