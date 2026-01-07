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

        /* Always show toggle in the config */
        if ($data->isConfigContext()) {
            return true;
        }

        if (! $data->set()->enabled()) {
            return false;
        }

        return $data->set()->config()->value('sitemap');
    }
}
