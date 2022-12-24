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

        $disabled = config("advanced-seo.disabled.{$data->type}", []);

        // Check if the collection/taxonomy is set to be disabled globally.
        if (in_array($data->handle, $disabled)) {
            return false;
        }

        // Terms are not yet supported.
        if ($data->type === 'taxonomies') {
            return false;
        }

        $enabledCollections = Seo::find('site', 'social_media')
            ?->in($data->locale)
            ?->value('social_images_generator_collections') ?? [];

        // Don't show the generator section if the collection is not configured.
        if ($data->type === 'collections' && ! in_array($data->handle, $enabledCollections)) {
            return false;
        }

        return true;
    }
}
