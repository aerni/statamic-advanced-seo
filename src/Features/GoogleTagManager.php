<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Context\Context;

class GoogleTagManager
{
    public static function enabled(Context $context): bool
    {
        return config('advanced-seo.analytics.google_tag_manager', true);
    }
}
