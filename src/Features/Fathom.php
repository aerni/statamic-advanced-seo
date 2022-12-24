<?php

namespace Aerni\AdvancedSeo\Features;

class Fathom
{
    public static function enabled(): bool
    {
        return config('advanced-seo.analytics.fathom', true);
    }
}
