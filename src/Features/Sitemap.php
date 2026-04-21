<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\AdvancedSeo;
use Aerni\AdvancedSeo\SeoSets\SeoSet;

class Sitemap extends Feature
{
    protected static function available(): bool
    {
        return AdvancedSeo::pro() && config('advanced-seo.sitemap.enabled', true);
    }

    protected static function enabledInLocalization(SeoSet $set): bool
    {
        return $set->config()->value('sitemap');
    }
}
