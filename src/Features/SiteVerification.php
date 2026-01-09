<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Context\Context;

class SiteVerification
{
    public static function enabled(Context $context): bool
    {
        return config('advanced-seo.site_verification', true);
    }
}
