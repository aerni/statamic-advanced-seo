<?php

namespace Aerni\AdvancedSeo\Tokens\Normalizers;

use Aerni\AdvancedSeo\Tokens\TokenNormalizer;
use Statamic\Fields\Value;
use Statamic\Support\Str;

class TextareaTokenNormalizer extends TokenNormalizer
{
    public function fieldtype(): string
    {
        return 'textarea';
    }

    public function normalize(Value $value): string
    {
        return Str::squish(strip_tags($value->value() ?? ''));
    }
}
