<?php

namespace Aerni\AdvancedSeo\Content;

use Statamic\Facades\URL;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\File;
use Statamic\Facades\AssetContainer;
use Statamic\Contracts\Entries\Entry;

class SocialImage
{
    public function __construct(protected Entry $entry, protected array $specs)
    {
        //
    }

    public function generate(): void
    {
        $this->ensureDirectoryExists();

        Browsershot::url($this->templateUrl())
            ->windowSize($this->specs['width'], $this->specs['height'])
            ->save($this->absolutePath());
    }

    public function exists(): bool
    {
        return File::exists($this->absolutePath());
    }

    public function absoluteUrl(): string
    {
        $container = config('advanced-seo.social_images.container', 'assets');

        return URL::assemble(AssetContainer::find($container)->absoluteUrl(), $this->path());
    }

    protected function templateUrl(): string
    {
        return url('/') . "/!/advanced-seo/social-images/{$this->specs['type']}/{$this->entry->id}?site={$this->entry->locale}&theme={$this->entry->seo_social_images_theme}";
    }

    protected function path(): string
    {
        return "social_images/{$this->entry->slug}-{$this->entry->locale}-{$this->specs['type']}.png";
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
}
