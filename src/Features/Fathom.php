<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Data\DefaultsData;

class Fathom
{
    public static function enabled(DefaultsData $data): bool
    {
        return config('advanced-seo.analytics.fathom', true);
    }
}
