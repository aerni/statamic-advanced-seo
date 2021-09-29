<?php

namespace Aerni\AdvancedSeo\Contracts;

interface Fields
{
    public static function make(): self;

    public function data($data): self;

    public function get(): array;

    public function items(): array;
}
