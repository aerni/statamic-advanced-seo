<?php

namespace Aerni\AdvancedSeo\Content;

use Facades\Statamic\Imaging\GlideServer;
use Illuminate\Support\Facades\File;
use Spatie\Browsershot\Browsershot;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\File as StatamicFile;
use Statamic\Facades\Folder;

class SocialImage
{
    public function __construct(protected Entry $entry, protected array $specs)
    {
        //
    }

    public function generate(): array
    {
        $this->ensureDirectoryExists();

        Browsershot::url($this->templateUrl())
            ->windowSize($this->specs['width'], $this->specs['height'])
            ->save($this->absolutePath());

        $this->clearGlideCache();

        return [$this->specs['field'] => $this->path()];
    }

    protected function templateUrl(): string
    {
        return "{$this->entry->site()->absoluteUrl()}/social-images/{$this->specs['type']}/{$this->entry->id()}";
    }

    protected function path(): string
    {
        return "social_images/{$this->entry->slug()}-{$this->entry->locale()}-{$this->specs['type']}.png";
    }

    protected function absolutePath($path = null): string
    {
        $container = config('advanced-seo.social_images.container', 'assets');

        return AssetContainer::find($container)->disk()->path($path ?? $this->path());
    }

    protected function ensureDirectoryExists(): void
    {
        $directory = $this->absolutePath(pathinfo($this->path(), PATHINFO_DIRNAME));

        File::ensureDirectoryExists($directory);
    }

    protected function clearGlideCache(): void
    {
        // Get the glide server cache path.
        $cachePath = GlideServer::cachePath();

        // Get the cached image path
        $filePath = collect(Folder::getFilesRecursively($cachePath))
            ->firstWhere(fn ($path) => str_contains($path, $this->path()));

        if ($filePath) {
            // Delete the cached image.
            StatamicFile::delete($filePath);
        }

        // Clean up subfolders.
        Folder::deleteEmptySubfolders($cachePath);
    }
}
