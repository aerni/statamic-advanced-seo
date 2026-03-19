<?php

namespace Aerni\AdvancedSeo\Tokens;

use Statamic\Fields\Value;

abstract class TokenNormalizer
{
    abstract public function fieldtype(): string;

    abstract public function normalize(Value $value): ?string;

    public static function register(): void
    {
        app('advanced-seo.tokens')->push(static::class);
    }
}
