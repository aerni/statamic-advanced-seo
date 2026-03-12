<?php

namespace Aerni\AdvancedSeo\Tokens\Normalizers;

use Aerni\AdvancedSeo\Tokens\TokenNormalizer;
use Statamic\Fields\Value;

class MarkdownTokenNormalizer extends TokenNormalizer
{
    public function fieldtype(): string
    {
        return 'markdown';
    }

    public function normalize(Value $value): string
    {
        return trim(strip_tags($value->value() ?? ''));
    }
}
