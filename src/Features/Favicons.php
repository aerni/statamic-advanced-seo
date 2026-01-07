<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Data\DefaultsData;

class Favicons
{
    public static function enabled(DefaultsData $data): bool
    {
        return config('advanced-seo.favicons.enabled', true);
    }
}
