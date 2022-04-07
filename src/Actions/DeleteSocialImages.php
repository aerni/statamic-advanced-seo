<?php

namespace Aerni\AdvancedSeo\Actions;

use Statamic\Contracts\Entries\Entry;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Actions\ClearImageGlideCache;

class DeleteSocialImages
{
    public static function handle(Entry $entry): void
    {
        SocialImage::all($entry)
            ->each(fn ($image) => $image->delete())
            ->each(fn ($image) => ClearImageGlideCache::handle($image->path()));
    }
}
