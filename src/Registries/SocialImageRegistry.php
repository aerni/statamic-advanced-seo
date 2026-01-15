<?php

namespace Aerni\AdvancedSeo\Registries;

use Aerni\AdvancedSeo\SocialImages\SocialImage;
use Aerni\AdvancedSeo\SocialImages\ThemeCollection;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;

class SocialImageRegistry extends Registry
{
    public function find(string $type): ?SocialImage
    {
        return $this->all()->firstWhere('type', $type);
    }

    public function openGraph(): SocialImage
    {
        return $this->find('open_graph');
    }

    public function twitter(): SocialImage
    {
        return $this->find('twitter_summary');
    }

    public function twitterLarge(): SocialImage
    {
        return $this->find('twitter_summary_large_image');
    }

    /**
     * Get all generators for an entry, auto-resolving the correct Twitter type.
     *
     * @return Collection<int, \Aerni\AdvancedSeo\SocialImages\SocialImageGenerator>
     */
    public function for(Entry $entry): Collection
    {
        return collect([
            $this->openGraph()->for($entry),
            $this->find("twitter_{$entry->seo_twitter_card}")->for($entry),
        ]);
    }

    /**
     * Get preview targets for an entry.
     */
    public function previewTargets(Entry $entry): array
    {
        $theme = $entry->seo_social_images_theme;

        return [
            [
                'label' => 'Open Graph Image',
                'format' => $this->openGraph()->url($theme, '{id}'),
            ],
            [
                'label' => 'Twitter Image',
                'format' => $this->find("twitter_{$entry->seo_twitter_card}")->url($theme, '{id}'),
            ],
        ];
    }

    public function themes(): ThemeCollection
    {
        return (new SocialImageThemeRegistry)->all();
    }

    protected function items(): array
    {
        return [
            new SocialImage(type: 'open_graph', handle: 'og_image'),
            new SocialImage(type: 'twitter_summary', handle: 'twitter_summary_image'),
            new SocialImage(type: 'twitter_summary_large_image', handle: 'twitter_summary_large_image'),
        ];
    }
}
