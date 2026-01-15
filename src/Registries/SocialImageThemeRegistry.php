<?php

namespace Aerni\AdvancedSeo\Registries;

use Aerni\AdvancedSeo\SocialImages\Theme;
use Aerni\AdvancedSeo\SocialImages\ThemeCollection;
use Illuminate\Support\Facades\File;

class SocialImageThemeRegistry extends Registry
{
    protected string $collection = ThemeCollection::class;

    public function find(string $handle): ?Theme
    {
        return $this->all()->firstWhere('handle', $handle);
    }

    protected function items(): array
    {
        $path = resource_path('views/social_images');

        if (! File::isDirectory($path)) {
            return [];
        }

        return array_map(
            fn ($path) => new Theme($path),
            File::directories($path)
        );
    }
}
