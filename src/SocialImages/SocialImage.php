<?php

namespace Aerni\AdvancedSeo\SocialImages;

use Aerni\AdvancedSeo\Facades\SocialImage as SocialImageApi;
use Aerni\AdvancedSeo\Models\SocialImageTheme;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;
use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Assets\AssetContainer as Container;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\AssetContainer;

class SocialImage
{
    public function __construct(protected Entry $entry, protected array $model)
    {
        //
    }

    public function generate(): self
    {
        $this->ensureDirectoryExists();

        Browsershot::url($this->templateUrl())
            ->windowSize($this->model['width'], $this->model['height'])
            ->waitUntilNetworkIdle()
            ->save($this->absolutePath());

        $this->container()->makeAsset($this->path())->save();

        return $this;
    }

    public function asset(): ?Asset
    {
        return $this->container()->asset($this->path());
    }

    public function delete(): void
    {
        $this->asset()?->delete();
    }

    public function path(): string
    {
        return "social_images/{$this->entry->collection}/{$this->filename()}";
    }

    protected function absolutePath($path = null): string
    {
        return $this->container()->disk()->path($path ?? $this->path());
    }

    protected function container(): Container
    {
        $container = config('advanced-seo.social_images.container', 'assets');

        return AssetContainer::find($container);
    }

    protected function filename(): string
    {
        $id = $this->entry->id;
        $type = Str::replace('_', '-', $this->model['group']);

        return "{$id}_{$type}.png";
    }

    protected function templateUrl(): string
    {
        return url('/').SocialImageApi::route(
            theme: $this->entry->seo_social_images_theme ?? SocialImageTheme::fieldtypeDefault(),
            type: $this->model['type'],
            id: $this->entry->id,
        );
    }

    protected function ensureDirectoryExists(): void
    {
        $directory = $this->absolutePath(pathinfo($this->path(), PATHINFO_DIRNAME));

        File::ensureDirectoryExists($directory);
    }
}
