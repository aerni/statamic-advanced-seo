<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Context\Context;

class Cloudflare
{
    public static function enabled(Context $context): bool
    {
        return config('advanced-seo.analytics.cloudflare_analytics', true);
    }
}
