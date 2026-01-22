<?php

namespace Aerni\AdvancedSeo\SocialImages;

use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;

class SocialImage
{
    public function __construct(
        public readonly string $type,
        public readonly string $handle,
    ) {}

    public function for(Entry|Term $content): SocialImageGenerator
    {
        return new SocialImageGenerator($this, $content);
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

    public function url(string $theme, string $id, string $site): string
    {
        return url("/!/advanced-seo/social-images/{$theme}/{$this->type}/{$id}/{$site}");
    }
}
