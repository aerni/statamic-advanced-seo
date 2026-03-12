<?php

namespace Aerni\AdvancedSeo\Tokens\Normalizers;

use Aerni\AdvancedSeo\Tokens\TokenNormalizer;
use Statamic\Fields\Value;

class TextareaTokenNormalizer extends TokenNormalizer
{
    public function fieldtype(): string
    {
        return 'textarea';
    }

    public function normalize(Value $value): string
    {
        return trim(strip_tags($value->value() ?? ''));
    }
}
