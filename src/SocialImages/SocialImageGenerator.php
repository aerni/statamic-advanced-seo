<?php

namespace Aerni\AdvancedSeo\SocialImages;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelScreenshot\Facades\Screenshot;
use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Assets\AssetContainer as Container;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\AssetContainer;

class SocialImageGenerator
{
    protected Container $container;

    public function __construct(
        protected SocialImage $socialImage,
        protected Entry|Term $content,
    ) {
        $this->container = AssetContainer::find(config('advanced-seo.social_images.container', 'assets'));
    }

    public function generate(): Asset
    {
        $this->ensureDirectoryExists();
        $this->deletePreviousImages();

        Screenshot::url($this->templateUrl())
            ->width($this->socialImage->width())
            ->height($this->socialImage->height())
            ->deviceScaleFactor(1)
            ->withBrowsershot(fn (Browsershot $browsershot) => $browsershot->preventUnsuccessfulResponse())
            ->save($this->absolutePath());

        $asset = $this->container->makeAsset($this->path());

        $asset->save();

        $this->cacheContentHash();

        return $asset;
    }

    /**
     * Find an existing generated image for this content.
     */
    public function asset(): ?Asset
    {
        return $this->assets()->first();
    }

    /**
     * Check if the image needs to be (re)generated.
     */
    public function isDirty(): bool
    {
        return Cache::get($this->contentHashCacheKey()) !== $this->contentHash()
            || ! $this->asset();
    }

    /**
     * A unique identifier for this content's generated images.
     * Terms share the same ID across localizations, so we append the locale.
     */
    protected function id(): string
    {
        $id = Str::replace('::', '_', $this->content->id());

        if ($this->content instanceof Term) {
            return "{$id}_{$this->content->locale()}";
        }

        return $id;
    }

    protected function directory(): string
    {
        $type = $this->content instanceof Entry ? 'collection' : 'taxonomy';

        $handle = $this->content instanceof Entry
            ? $this->content->collection()->handle()
            : $this->content->taxonomy()->handle();

        return "social_images/{$type}-{$handle}";
    }

    protected function path(): string
    {
        return "{$this->directory()}/{$this->filename()}";
    }

    protected function absolutePath(?string $path = null): string
    {
        return $this->container->disk()->path($path ?? $this->path());
    }

    /**
     * Each generation produces a unique filename with a timestamp.
     * This ensures a new asset ID each time, preventing browser/Glide cache issues.
     */
    protected function filename(): string
    {
        $timestamp = once(fn () => now()->timestamp);

        return "{$this->id()}_{$timestamp}.png";
    }

    /**
     * Find all existing generated images for this content.
     *
     * @return Collection<int, Asset>
     */
    protected function assets(): Collection
    {
        $directory = pathinfo($this->path(), PATHINFO_DIRNAME);

        return $this->container->queryAssets()
            ->where('path', 'like', "{$directory}/{$this->id()}_%")
            ->get();
    }

    /**
     * Delete all previously generated images for this content.
     */
    protected function deletePreviousImages(): void
    {
        $this->assets()->each->delete();
    }

    protected function ensureDirectoryExists(): void
    {
        $directory = $this->absolutePath(pathinfo($this->path(), PATHINFO_DIRNAME));

        File::ensureDirectoryExists($directory);
    }

    protected function templateUrl(): string
    {
        return $this->socialImage->url(
            $this->content->seo_social_images_theme,
            $this->content->id(),
            $this->content->locale()
        );
    }

    /**
     * Cache the current content hash so isDirty() returns false until the content changes.
     */
    protected function cacheContentHash(): void
    {
        Cache::forever($this->contentHashCacheKey(), $this->contentHash());
    }

    /**
     * Hash the rendered social image template HTML.
     * Only changes when the actual template output changes,
     * regardless of which entry fields were modified.
     */
    protected function contentHash(): string
    {
        return md5(once(fn () => Http::get($this->templateUrl())->body()));
    }

    protected function contentHashCacheKey(): string
    {
        return "advanced-seo.social-image.{$this->id()}";
    }
}
