<?php

namespace Aerni\AdvancedSeo\Features;

class Favicons
{
    public static function enabled(): bool
    {
        return config('advanced-seo.favicons.enabled', true);
    }
}
