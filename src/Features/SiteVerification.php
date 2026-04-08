<?php

namespace Aerni\AdvancedSeo\Features;

class SiteVerification extends Feature
{
    protected static function available(): bool
    {
        return config('advanced-seo.site_verification', true);
    }
}
