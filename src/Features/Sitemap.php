<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Context\Context;

class Sitemap extends Feature
{
    public static function enabled(?Context $context = null): bool
    {
        if (! config('advanced-seo.sitemap.enabled', true)) {
            return false;
        }

        if (! $context) {
            return true;
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
