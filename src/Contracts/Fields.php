<?php

namespace Aerni\AdvancedSeo\Contracts;

use Aerni\AdvancedSeo\Data\DefaultsData;

interface Fields
{
    public static function make(): self;

    public function data(DefaultsData $data): self;

    public function get(): array;

    public function items(): array;
}
