<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Data\DefaultsData;

class GoogleTagManager
{
    public static function enabled(DefaultsData $data): bool
    {
        return config('advanced-seo.analytics.google_tag_manager', true);
    }
}
