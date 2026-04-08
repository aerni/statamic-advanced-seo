<?php

namespace Aerni\AdvancedSeo\Features;

class GoogleTagManager extends Feature
{
    protected static function available(): bool
    {
        return config('advanced-seo.analytics.google_tag_manager', true);
    }
}
