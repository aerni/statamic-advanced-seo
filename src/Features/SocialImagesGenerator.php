<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Data\DefaultsData;

class SocialImagesGenerator
{
    public static function enabled(DefaultsData $data): bool
    {
        if (! config('advanced-seo.social_images.generator.enabled', false)) {
            return false;
        }

        if ($data->type === 'taxonomies') {
            return false;
        }

        /* Always show toggle in the config */
        if ($data->isConfigContext()) {
            return true;
        }

        if (! $data->set()->enabled()) {
            return false;
        }

        return $data->set()->config()->value('social_images_generator');
    }
}
