<?php

namespace Aerni\AdvancedSeo\Content;

use Illuminate\Support\Facades\File;
use Spatie\Browsershot\Browsershot;
use Statamic\Contracts\Assets\AssetContainer as Container;
use Statamic\Facades\AssetContainer;

class SocialImage
{
    protected string $id;
    protected string $basename;
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

    public function id(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function basename(string $basename): self
    {
        $this->basename = $basename;

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
        return config('app.url') . "/seo/social-images/{$type}/" . $this->id;
    }

    public function path(string $type): string
    {
        return "social_images/{$this->basename}-{$type}-{$this->timestamp}.png";
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
