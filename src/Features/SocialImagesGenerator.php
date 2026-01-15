<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Facades\SocialImageTheme;

class SocialImagesGenerator
{
    public static function enabled(Context $context): bool
    {
        if (! config('advanced-seo.social_images.generator.enabled', false)) {
            return false;
        }

        if (SocialImageTheme::all()->isEmpty()) {
            return false;
        }

        if ($context->type === 'taxonomies') {
            return false;
        }

        /* Always show toggle in the config */
        if ($context->isConfig()) {
            return true;
        }

        if (! $context->seoSet()->enabled()) {
            return false;
        }

        return $context->seoSet()->config()->value('social_images_generator');
    }
}
