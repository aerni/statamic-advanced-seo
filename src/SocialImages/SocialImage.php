<?php

namespace Aerni\AdvancedSeo\SocialImages;

use Statamic\Contracts\Entries\Entry;

class SocialImage
{
    public function __construct(
        public readonly string $type,
        public readonly string $handle,
    ) {}

    public function for(Entry $entry): SocialImageGenerator
    {
        return new SocialImageGenerator($this, $entry);
    }

    public function width(): int
    {
        return config("advanced-seo.social_images.presets.{$this->type}.width");
    }

    public function height(): int
    {
        return config("advanced-seo.social_images.presets.{$this->type}.height");
    }

    public function sizeString(): string
    {
        return "{$this->width()} x {$this->height()} pixels";
    }
}
