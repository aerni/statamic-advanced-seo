<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Statamic\Console\Processes\Composer;

class SocialImagesGenerator extends Feature
{
    public static function enabled(?Context $context = null): bool
    {
        if (! config('advanced-seo.social_images.generator.enabled', false)) {
            return false;
        }

        if (! app(Composer::class)->isInstalled('spatie/laravel-screenshot')) {
            return false;
        }

        if (SocialImage::themes()->all()->isEmpty()) {
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

        return $context->seoSet()->config()->value('social_images_generator');
    }
}
