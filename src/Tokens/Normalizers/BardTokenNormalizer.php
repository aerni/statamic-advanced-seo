<?php

namespace Aerni\AdvancedSeo\Tokens\Normalizers;

use Aerni\AdvancedSeo\Tokens\TokenNormalizer;
use Statamic\Fields\Value;
use Statamic\Modifiers\CoreModifiers;

class BardTokenNormalizer extends TokenNormalizer
{
    public function fieldtype(): string
    {
        return 'bard';
    }

    public function normalize(Value $value): string
    {
        return (new CoreModifiers)->bardText($value);
    }
}
