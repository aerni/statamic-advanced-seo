<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Context\Context;

class GoogleTagManager extends Feature
{
    public static function enabled(?Context $context = null): bool
    {
        return config('advanced-seo.analytics.google_tag_manager', true);
    }
}
