<?php

namespace Aerni\AdvancedSeo\Support;

use Illuminate\Support\Str;

class Helpers
{
    public static function parseLocale(string $locale): string
    {
        $parsed = preg_replace('/\.utf8/i', '', $locale);

        return \WhiteCube\Lingua\Service::create($parsed)->toW3C();
    }

    public static function isAddonRoute(): bool
    {
        return Str::containsAll(request()->path(), [config('statamic.cp.route', 'cp'), 'advanced-seo']);
    }

    public static function isActionRoute(): bool
    {
        return Str::containsAll(request()->path(), ['!', 'advanced-seo']);
    }
}
