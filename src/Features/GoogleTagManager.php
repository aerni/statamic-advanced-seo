<?php

namespace Aerni\AdvancedSeo\Features;

class GoogleTagManager
{
    public static function enabled(): bool
    {
        return config('advanced-seo.analytics.google_tag_manager', true);
    }
}
