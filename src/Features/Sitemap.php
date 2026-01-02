<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Data\DefaultsData;

class Sitemap
{
    public static function enabled(DefaultsData $data): bool
    {
        if (! config('advanced-seo.sitemap.enabled', true)) {
            return false;
        }

        return $data->set()->config()->get('sitemap', true);
    }
}
