<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Context\Context;

class Favicons
{
    public static function enabled(Context $context): bool
    {
        return config('advanced-seo.favicons.enabled', true);
    }
}
