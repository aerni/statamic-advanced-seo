<?php

namespace Aerni\AdvancedSeo\SocialImages;

use Aerni\AdvancedSeo\Facades\SocialImageTheme;
use Illuminate\Support\Facades\File;
use Spatie\Browsershot\Browsershot;
use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Assets\AssetContainer as Container;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\AssetContainer;

class SocialImageGenerator
{
    protected Container $container;

    public function __construct(
        protected SocialImage $socialImage,
        protected Entry $entry,
    ) {
        $this->container = AssetContainer::find(config('advanced-seo.social_images.container', 'assets'));
    }

    public function generate(): self
    {
        $this->ensureDirectoryExists();

        Browsershot::url($this->templateUrl())
            ->windowSize($this->socialImage->width(), $this->socialImage->height())
            ->preventUnsuccessfulResponse()
            ->waitUntilNetworkIdle()
            ->save($this->absolutePath());

        $this->container->makeAsset($this->path())->save();

        return $this;
    }

    public function asset(): ?Asset
    {
        return $this->container->asset($this->path());
    }

    public function delete(): void
    {
        $this->asset()?->delete();
    }

    protected function path(): string
    {
        return "social_images/{$this->entry->collection}/{$this->filename()}";
    }

    protected function absolutePath(?string $path = null): string
    {
        return $this->container->disk()->path($path ?? $this->path());
    }

    protected function filename(): string
    {
        $group = str_starts_with($this->socialImage->type, 'twitter_') ? 'twitter' : 'open-graph';

        return "{$this->entry->id}_{$group}.png";
    }

    protected function templateUrl(): string
    {
        // TODO: It would be nice if we could just do $this->entry->seo_social_images_theme
        // and the theme field resolves itself. Maybe in the future with its own fieldtype.
        return $this->socialImage->url(
            SocialImageTheme::resolveFor($this->entry)->handle,
            $this->entry->id
        );
    }

    protected function ensureDirectoryExists(): void
    {
        $directory = $this->absolutePath(pathinfo($this->path(), PATHINFO_DIRNAME));

        File::ensureDirectoryExists($directory);
    }
}
