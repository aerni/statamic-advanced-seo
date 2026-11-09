<?php

namespace Aerni\AdvancedSeo\SocialImages;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use League\Flysystem\UnableToRetrieveMetadata;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelScreenshot\Facades\Screenshot;
use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Assets\AssetContainer as Container;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Stache;
use Statamic\Statamic;

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
     * Find generated images for this content whose files are still on disk.
     *
     * The asset index can keep a path after the file disappears. Hydrating
     * that record asks Flysystem for the file size and throws.
     *
     * @return Collection<int, Asset>
     */
    protected function assets(): Collection
    {
        return $this->matchingAssets()
            ->filter(fn (Asset $asset) => $this->fileExists($asset))
            ->values();
    }

    /**
     * Every generated image record for this content, including missing files.
     *
     * @return Collection<int, Asset>
     */
    protected function matchingAssets(): Collection
    {
        $directory = pathinfo($this->path(), PATHINFO_DIRNAME);

        return $this->container->queryAssets()
            ->where('path', 'like', "{$directory}/{$this->id()}_%")
            ->get();
    }

    /**
     * Delete all previously generated images for this content.
     *
     * Missing files are dropped from the asset index without reading metadata.
     * Asset::delete() fires AssetDeleted, and that listener hydrates the asset.
     */
    protected function deletePreviousImages(): void
    {
        $this->matchingAssets()->each(fn (Asset $asset) => $this->removeAsset($asset));
    }

    protected function removeAsset(Asset $asset): void
    {
        $disk = $this->container->disk();

        try {
            $disk->delete($asset->path());
            $disk->delete($asset->metaPath());
        } catch (UnableToRetrieveMetadata) {
            // The file is already gone. The index record still has to be removed.
        }

        $this->forgetAssetListing($asset->path());
        $this->forgetAssetListing($asset->metaPath());

        Stache::store('assets::'.$this->container->handle())->delete($asset);

        $asset->cacheStore()->forget($asset->metaCacheKey());
    }

    /**
     * Drop a path from the container's cached file listing.
     *
     * Queue workers re-read that listing when Statamic saves it, so a forget
     * made in memory is written back with the deleted path still present.
     */
    protected function forgetAssetListing(string $path): void
    {
        $contents = $this->container->contents();
        $store = $contents->cacheStore();
        $key = 'asset-list-contents-'.$this->container->handle();
        $ttl = Stache::isWatcherEnabled() ? 0 : null;

        $forget = function () use ($store, $key, $path, $ttl) {
            $cached = $store->get($key);

            if ($cached === null) {
                return;
            }

            $store->put($key, collect($cached)->forget($path), $ttl);
        };

        try {
            $store->lock('advanced-seo.asset-listing.'.$this->container->handle(), 5)->block(5, $forget);
        } catch (\Throwable) {
            $forget();
        }

        if (! Statamic::isWorker()) {
            $contents->forget($path);
        }
    }

    protected function fileExists(Asset $asset): bool
    {
        return $this->container->disk()->exists($asset->path());
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
        return md5(once(fn () => Http::timeout(10)->get($this->templateUrl())->throw()->body()));
    }

    protected function contentHashCacheKey(): string
    {
        return "advanced-seo.social-image.{$this->id()}";
    }
}
