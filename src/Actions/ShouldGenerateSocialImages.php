<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Features\SocialImagesGenerator;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Statamic;

class ShouldGenerateSocialImages
{
    public static function handle(Entry $entry): bool
    {
        // Don't generate if the social images generator feature is disabled.
        if (! SocialImagesGenerator::enabled(GetDefaultsData::handle($entry))) {
            return false;
        }

        // Don't generate if the entry is saved when first localizing an entry.
        if (Statamic::isCpRoute() && Str::contains(request()->path(), 'localize')) {
            return false;
        }

        // Don't generate if the entry is saved when an action is performed on the listing view.
        if (Statamic::isCpRoute() && Str::contains(request()->path(), 'actions')) {
            return false;
        }

        // Only generate if the social images generator is turned on.
        return $entry->seo_generate_social_images;
    }
}
