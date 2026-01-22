<?php

namespace Aerni\AdvancedSeo\Registries;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\SocialImages\Theme;
use Aerni\AdvancedSeo\SocialImages\ThemeCollection;
use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;

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
     * Resolve the effective theme for content.
     */
    public function resolveFor(Entry|Term $content): Theme
    {
        $allowedThemes = $this->allowedFor(Context::from($content)->seoSet());

        return $allowedThemes->firstWhere('handle', Helpers::localizedContent($content)->seo_social_images_theme)
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
