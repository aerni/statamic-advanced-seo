<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Context\Context;

class Fathom
{
    public static function enabled(Context $context): bool
    {
        return config('advanced-seo.analytics.fathom', true);
    }
}
