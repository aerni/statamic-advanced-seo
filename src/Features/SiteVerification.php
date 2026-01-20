<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Context\Context;

class SiteVerification extends Feature
{
    public static function enabled(?Context $context = null): bool
    {
        return config('advanced-seo.site_verification', true);
    }
}
