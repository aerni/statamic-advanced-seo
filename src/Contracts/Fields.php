<?php

namespace Aerni\AdvancedSeo\Contracts;

interface Fields
{
    public static function make(): self;

    public static function resolve(mixed $model = null): array;

    public function for(mixed $model): self;

    public function get(): array;

    public function items(): array;
}
