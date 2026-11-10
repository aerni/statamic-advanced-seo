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

    /**
     * Generate the social image for this content.
     *
     * A page render and a queued job can both call this while a screenshot is
     * still running. Each run writes a new file and then deletes the other
     * files for this entry, so the second run removes the PNG the first run
     * just wrote.
     *
     * A page render waits for the image, but gives up before PHP times out the
     * request. A queued job waits so it still writes the image after an
     * in-request generation finishes. A later run whose template still matches
     * the stored hash returns the existing image. A template that changed
     * since that hash still generates.
     */
    public function generate(): Asset
    {
        $lock = "advanced-seo.social-image.generate.{$this->id()}";
        $wait = Statamic::isWorker() ? 170 : 20;

        return Cache::lock($lock, 180)->block($wait, function () {
            if (($existing = $this->asset()) && ! $this->isDirty()) {
                return $existing;
            }

            $this->ensureDirectoryExists();

            Screenshot::url($this->templateUrl())
                ->width($this->socialImage->width())
                ->height($this->socialImage->height())
                ->deviceScaleFactor(1)
                ->withBrowsershot(fn (Browsershot $browsershot) => $browsershot->preventUnsuccessfulResponse())
                ->save($this->absolutePath());

            $this->deletePreviousImages();

            $asset = $this->container->makeAsset($this->path());

            $asset->save();

            $this->cacheContentHash();

            return $asset;
        });
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
     * Delete previously generated images for this content.
     * The new file is excluded so a same-second run does not remove the PNG it just wrote.
     */
    protected function deletePreviousImages(): void
    {
        $this->assets()
            ->reject(fn (Asset $asset) => $asset->path() === $this->path())
            ->each->delete();
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
