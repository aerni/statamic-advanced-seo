<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\AdvancedSeo;
use Aerni\AdvancedSeo\Context\Context;
use Facades\Statamic\Console\Processes\Composer;

class EloquentDriver extends Feature
{
    public static function enabled(?Context $context = null): bool
    {
        if (! AdvancedSeo::pro()) {
            return false;
        }

        if (! Composer::isInstalled('statamic/eloquent-driver')) {
            return false;
        }

        return config('advanced-seo.driver') === 'eloquent';
    }
}
