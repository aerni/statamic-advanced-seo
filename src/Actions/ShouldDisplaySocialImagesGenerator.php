<?php

namespace Aerni\AdvancedSeo\Actions;

use Statamic\Facades\Site;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Data\DefaultsData;

class ShouldDisplaySocialImagesGenerator
{
    // TODO: Use Blink.
    public static function handle(DefaultsData $data): bool
    {
        // Don't show the generator section if the generator is disabled.
        if (! config('advanced-seo.social_images.generator.enabled', false)) {
            return false;
        }

        // Terms are not yet supported.
        if ($data->type === 'taxonomies') {
            return false;
        }

        $enabledCollections = Seo::find('site', 'social_media')
            ?->in(Site::selected()->handle())
            ?->value('social_images_generator_collections') ?? [];

        // Don't show the generator section if the collection is not configured.
        if ($data->type === 'collections' && ! in_array($data->handle, $enabledCollections)) {
            return false;
        }

        return true;
    }
}
