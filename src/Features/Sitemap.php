<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Context\Context;

class Sitemap
{
    public static function enabled(Context $context): bool
    {
        if (! config('advanced-seo.sitemap.enabled', true)) {
            return false;
        }

        /* Always show toggle in the config */
        if ($context->isConfig()) {
            return true;
        }

        if (! $context->seoSet()->enabled()) {
            return false;
        }

        return $context->seoSet()->config()->value('sitemap');
    }
}
