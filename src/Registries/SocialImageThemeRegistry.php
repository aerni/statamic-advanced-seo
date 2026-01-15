<?php

namespace Aerni\AdvancedSeo\Registries;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\SocialImages\Theme;
use Aerni\AdvancedSeo\SocialImages\ThemeCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Statamic\Contracts\Entries\Entry;

class SocialImageThemeRegistry extends Registry
{
    protected string $collection = ThemeCollection::class;

    public function find(string $handle): ?Theme
    {
        return $this->all()->firstWhere('handle', $handle);
    }

    /**
     * Get allowed themes for a SeoSet.
     */
    public function allowedFor(SeoSet $seoSet): ThemeCollection
    {
        return $this->all()
            ->whereIn('handle', Arr::wrap($seoSet->config()->value('social_images_themes')))
            ->values();
    }

    /**
     * Resolve the effective theme for an entry.
     */
    public function resolveFor(Entry $entry): Theme
    {
        $allowedThemes = $this->allowedFor(Context::from($entry)->seoSet());

        return $allowedThemes->firstWhere('handle', $entry->seo_social_images_theme)
            ?? $allowedThemes->default();
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
