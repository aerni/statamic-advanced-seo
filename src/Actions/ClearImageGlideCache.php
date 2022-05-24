<?php

namespace Aerni\AdvancedSeo\Actions;

use Statamic\Facades\File;
use Statamic\Facades\Glide;

class ClearImageGlideCache
{
    public static function handle(string $path): void
    {
        // Clear the glide path cache.
        Glide::cacheStore()->flush();

        // Get the glide cache disk.
        $disk = File::disk(Glide::cacheDisk());

        // Get the path of the cached image.
        $cachePath = $disk->getFilesRecursively('/')
            ->filter(fn ($cachePath) => str_contains($cachePath, $path))
            ->first();

        // Delete the cached image.
        if ($cachePath) {
            $disk->delete($cachePath);
        }
    }
}
