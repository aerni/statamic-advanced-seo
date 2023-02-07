<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Facades\Seo;

class SocialImagesGenerator
{
    public static function enabled(DefaultsData $data): bool
    {
        // Don't show the generator section if the generator is disabled.
        if (! config('advanced-seo.social_images.generator.enabled', false)) {
            return false;
        }

        // Always show the generator setting fields in the social media defaults.
        if ($data->type === 'site' && $data->handle === 'social_media') {
            return true;
        }

        $disabled = config("advanced-seo.disabled.{$data->type}", []);

        // Hide the generator if the collection/taxonomy is disabled in the config.
        if (in_array($data->handle, $disabled)) {
            return false;
        }

        $enabled = Seo::find('site', 'social_media')
            ?->in($data->locale)
            ?->value("social_images_generator_{$data->type}") ?? [];

        // Don't show the generator section if the collection/taxonomy is not configured.
        if (! in_array($data->handle, $enabled)) {
            return false;
        }

        return true;
    }
}
