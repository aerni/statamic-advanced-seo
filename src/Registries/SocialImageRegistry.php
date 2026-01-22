<?php

namespace Aerni\AdvancedSeo\Registries;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Facades\SocialImageTheme;
use Aerni\AdvancedSeo\SocialImages\SocialImage;
use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;

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
     * Get all generators for content, auto-resolving the correct Twitter type.
     *
     * @return Collection<int, \Aerni\AdvancedSeo\SocialImages\SocialImageGenerator>
     */
    public function for(Entry|Term $content): Collection
    {
        $content = Helpers::localizedContent($content);

        return collect([
            $this->openGraph()->for($content),
            $this->find("twitter_{$content->seo_twitter_card}")->for($content),
        ]);
    }

    /**
     * Get preview targets for content.
     */
    public function previewTargets(Entry|Term $content): array
    {
        $content = Helpers::localizedContent($content);
        $theme = SocialImageTheme::resolveFor($content)->handle;

        /**
         * Use preview data if available (during preview POST), otherwise use saved value.
         * When source is 'default', get the actual default from the SEO set since
         * the form's value field may still contain the old custom value.
         */
        $previewData = request()->input('preview.seo_twitter_card');

        if ($previewData) {
            $twitterCard = $previewData['source'] === 'default'
                ? Context::from($content)->seoSetLocalization()->seo_twitter_card
                : $previewData['value'];
        } else {
            $twitterCard = $content->seo_twitter_card;
        }

        return [
            [
                'label' => 'Open Graph Image',
                'format' => $this->openGraph()->url($theme, '{id}', $content->locale()),
            ],
            [
                'label' => 'Twitter Image',
                'format' => $this->find("twitter_{$twitterCard}")->url($theme, '{id}', $content->locale()),
            ],
        ];
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
