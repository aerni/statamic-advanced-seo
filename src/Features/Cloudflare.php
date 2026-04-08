<?php

namespace Aerni\AdvancedSeo\Features;

class Cloudflare extends Feature
{
    protected static function available(): bool
    {
        return config('advanced-seo.analytics.cloudflare_analytics', true);
    }
}
