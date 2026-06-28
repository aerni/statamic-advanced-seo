<?php

namespace Aerni\AdvancedSeo\Enums;

use Statamic\Support\Str;

enum MatchType: string
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

    public function label(): string
    {
        return match ($this) {
            self::Exact => __('advanced-seo::messages.match_type_exact'),
            self::Wildcard => __('advanced-seo::messages.match_type_wildcard'),
            self::Regex => __('advanced-seo::messages.match_type_regex'),
        };
    }
}
