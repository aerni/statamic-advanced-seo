<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Context\Context;

class Favicons extends Feature
{
    public static function enabled(?Context $context = null): bool
    {
        return config('advanced-seo.favicons.enabled', true);
    }
}
