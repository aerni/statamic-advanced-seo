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
            ->each(fn ($image) => ClearImageGlideCache::handle($image->path()));
    }
}
