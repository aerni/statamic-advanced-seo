<?php

namespace Aerni\AdvancedSeo\Registries;

use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\SocialImages\Theme;
use Aerni\AdvancedSeo\SocialImages\ThemeCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class SocialImageThemeRegistry extends Registry
{
    protected string $collection = ThemeCollection::class;

    public function find(string $handle): ?Theme
    {
        return $this->all()->firstWhere('handle', $handle);
    }

    /**
     * Get allowed themes for a SeoSet, preserving the user-defined order.
     */
    public function allowedFor(SeoSet $seoSet): ThemeCollection
    {
        $handles = Arr::wrap($seoSet->config()->value('social_images_themes'));
        $order = array_flip($handles);

        $themes = $this->all()
            ->whereIn('handle', $handles)
            ->sortBy(fn (Theme $theme) => $order[$theme->handle])
            ->values();

        return $themes->isNotEmpty() ? $themes : $this->all();
    }

    protected function items(): array
    {
        $path = resource_path('views/social_images');

        if (! File::isDirectory($path)) {
            return [];
        }

        return collect(File::directories($path))
            ->filter(fn (string $directory) => File::files($directory) !== [])
            ->mapInto(Theme::class)
            ->all();
    }
}
