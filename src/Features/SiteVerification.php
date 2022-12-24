<?php

namespace Aerni\AdvancedSeo\Features;

class SiteVerification
{
    public static function enabled(): bool
    {
        return config('advanced-seo.site_verification', true);
    }
}
