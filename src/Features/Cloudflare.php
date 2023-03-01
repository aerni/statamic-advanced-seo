<?php

namespace Aerni\AdvancedSeo\Features;

class Cloudflare
{
    public static function enabled(): bool
    {
        return config('advanced-seo.analytics.cloudflare_analytics', true);
    }
}
