<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Statamic\Contracts\Entries\Entry;

class DeleteSocialImages
{
    public static function handle(Entry $entry): void
    {
        SocialImage::all($entry)
            ->each(fn ($image) => $image->delete())
            // TODO: Do we still need this, as we are now deleting an actual asset, that clears cache in its delete method.
            ->each(fn ($image) => ClearImageGlideCache::handle($image->path()));
    }
}
