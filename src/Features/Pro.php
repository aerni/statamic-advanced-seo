<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\AdvancedSeo;
use Aerni\AdvancedSeo\Context\Context;

class Pro extends Feature
{
    public static function enabled(?Context $context = null): bool
    {
        return AdvancedSeo::pro();
    }
}
