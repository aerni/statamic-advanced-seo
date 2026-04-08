<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\AdvancedSeo;
use Aerni\AdvancedSeo\Contracts\SeoSetConfig;

class Sitemap extends Feature
{
    protected static function available(): bool
    {
        return AdvancedSeo::pro() && config('advanced-seo.sitemap.enabled', true);
    }

    protected static function enabledInConfig(SeoSetConfig $config): bool
    {
        return $config->value('sitemap');
    }
}
