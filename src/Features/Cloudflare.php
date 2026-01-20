<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Context\Context;

class Cloudflare extends Feature
{
    public static function enabled(?Context $context = null): bool
    {
        return config('advanced-seo.analytics.cloudflare_analytics', true);
    }
}
