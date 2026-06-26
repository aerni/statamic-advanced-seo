<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\AdvancedSeo;

class Redirects extends Feature
{
    protected static function available(): bool
    {
        return AdvancedSeo::pro() && config('advanced-seo.redirects.enabled', true);
    }
}
