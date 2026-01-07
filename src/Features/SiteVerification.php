<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Data\DefaultsData;

class SiteVerification
{
    public static function enabled(DefaultsData $data): bool
    {
        return config('advanced-seo.site_verification', true);
    }
}
