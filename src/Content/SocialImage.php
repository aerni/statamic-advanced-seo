<?php

namespace Aerni\AdvancedSeo\Content;

use Illuminate\Support\Facades\File;
use Spatie\Browsershot\Browsershot;
use Statamic\Contracts\Assets\AssetContainer as Container;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\AssetContainer;

class SocialImage
{
    protected Entry $entry;
    protected string $timestamp;

    protected array $types = [
        'og' => [
            'width' => 1200,
            'height' => 630,
        ],
        'twitter' => [
            'width' => 1200,
            'height' => 600,
        ],
    ];

    public function __construct()
    {
        $this->timestamp = time();
    }

    public function entry(Entry $entry): self
    {
        $this->entry = $entry;

        return $this;
    }

    public function generate(): self
    {
        $this->ensureDirectoryExists();

        foreach ($this->types as $type => $item) {
            Browsershot::url($this->templateUrl($type))
                ->windowSize($item['width'], $item['height'])
                ->save($this->container()->disk()->path($this->path($type)));
        }

        return $this;
    }

    public function toArray(): array
    {
        $images = [];

        foreach ($this->types as $type => $item) {
            $images["seo_{$type}_image"] = $this->path($type);
        }

        return $images;
    }

    public function templateUrl(string $type): string
    {
        return "{$this->entry->site()->absoluteUrl()}/seo/social-images/{$type}/{$this->entry->id()}";
    }

    public function path(string $type): string
    {
        return "social_images/{$this->entry->slug()}-{$type}-{$this->timestamp}.png";
    }

    protected function ensureDirectoryExists(): void
    {
        $directory = $this->container()->disk()->path('social_images');

        File::ensureDirectoryExists($directory);
    }

    public function container(): Container
    {
        $container = config('advanced-seo.social-images.container', 'assets');

        return AssetContainer::find($container);
    }
}
