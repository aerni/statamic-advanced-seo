<?php

namespace Aerni\AdvancedSeo\SocialImages;

use Aerni\AdvancedSeo\Facades\SocialImageTheme;
use Illuminate\Support\Facades\File;
use Spatie\Browsershot\Browsershot;
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
        $handle = $this->content instanceof Entry
            ? $this->content->collection()->handle()
            : $this->content->taxonomy()->handle();

        return "social_images/{$handle}/{$this->filename()}";
    }

    protected function absolutePath(?string $path = null): string
    {
        return $this->container->disk()->path($path ?? $this->path());
    }

    protected function filename(): string
    {
        $group = str_starts_with($this->socialImage->type, 'twitter_') ? 'twitter' : 'open-graph';

        // Entries have unique IDs per localization, but terms share the same ID (taxonomy::slug)
        // across localizations. Use slug + locale for terms to ensure unique filenames.
        if ($this->content instanceof Term) {
            return "{$this->content->slug()}_{$this->content->locale()}_{$group}.png";
        }

        return "{$this->content->id()}_{$group}.png";
    }

    protected function templateUrl(): string
    {
        // TODO: It would be nice if we could just do $this->entry->seo_social_images_theme
        // and the theme field resolves itself. Maybe in the future with its own fieldtype.
        return $this->socialImage->url(
            SocialImageTheme::resolveFor($this->content)->handle,
            $this->content->id(),
            $this->content->locale()
        );
    }

    protected function ensureDirectoryExists(): void
    {
        $directory = $this->absolutePath(pathinfo($this->path(), PATHINFO_DIRNAME));

        File::ensureDirectoryExists($directory);
    }
}
