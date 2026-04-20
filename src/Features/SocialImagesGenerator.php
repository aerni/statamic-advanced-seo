<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\AdvancedSeo;
use Aerni\AdvancedSeo\Contracts\SeoSetConfig;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Statamic\Console\Processes\Composer;

class SocialImagesGenerator extends Feature
{
    protected static function available(): bool
    {
        if (! AdvancedSeo::pro()) {
            return false;
        }

        if (! config('advanced-seo.social_images.generator.enabled', false)) {
            return false;
        }

        if (! app(Composer::class)->isInstalled('spatie/laravel-screenshot')) {
            return false;
        }

        return SocialImage::themes()->all()->isNotEmpty();
    }

    protected static function enabledInConfig(SeoSetConfig $config): bool
    {
        return $config->value('social_images_generator');
    }
}
