<?php

namespace Aerni\AdvancedSeo\Actions;

use Facades\Statamic\Imaging\GlideServer;
use Statamic\Facades\File;
use Statamic\Facades\Folder;

class ClearImageGlideCache
{
    public static function handle(string $path): void
    {
        // Get the glide server cache path.
        $cachePath = GlideServer::cachePath();

        // Get the cached image path
        $filePath = collect(Folder::getFilesRecursively($cachePath))
            ->firstWhere(fn ($filePath) => str_contains($filePath, $path));

        // Delete the cached image.
        if ($filePath) {
            File::delete($filePath);
        }
    }
}
