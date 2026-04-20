<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\AdvancedSeo;
use Facades\Statamic\Console\Processes\Composer;

class EloquentDriver extends Feature
{
    protected static function available(): bool
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
