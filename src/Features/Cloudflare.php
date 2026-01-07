<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Data\DefaultsData;

class Cloudflare
{
    public static function enabled(DefaultsData $data): bool
    {
        return config('advanced-seo.analytics.cloudflare_analytics', true);
    }
}
