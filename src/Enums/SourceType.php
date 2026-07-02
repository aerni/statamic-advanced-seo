<?php

namespace Aerni\AdvancedSeo\Enums;

use Statamic\Support\Str;

enum SourceType: string
{
    case Exact = 'exact';
    case Wildcard = 'wildcard';
    case Regex = 'regex';

    public static function fromSource(string $source): self
    {
        return match (true) {
            Str::startsWith($source, '#') => self::Regex,
            Str::contains($source, '*') => self::Wildcard,
            default => self::Exact,
        };
    }
}
