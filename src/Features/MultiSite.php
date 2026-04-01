<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\AdvancedSeo;
use Aerni\AdvancedSeo\Context\Context;
use Statamic\Facades\Site;

class MultiSite extends Feature
{
    public static function enabled(?Context $context = null): bool
    {
        if (! AdvancedSeo::pro()) {
            return false;
        }

        return Site::hasMultiple();
    }
}
