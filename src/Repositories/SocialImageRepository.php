<?php

namespace Aerni\AdvancedSeo\Repositories;

use Aerni\AdvancedSeo\Content\SocialImage;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Taxonomies\LocalizedTerm;

class SocialImageRepository
{
    public function all(Entry $entry): array
    {
        return $this->types()->flatMap(function ($item, $type) use ($entry) {
            return match ($type) {
                'twitter' => $this->twitter($entry),
                'og' => $this->openGraph($entry),
            };
        })->toArray();
    }

    public function openGraph(Entry $entry): array
    {
        return (new SocialImage($entry, $this->specs('og')))->generate();
    }

    public function twitter(Entry $entry): array
    {
        return (new SocialImage($entry, $this->specs('twitter', $entry)))->generate();
    }

    public function specs(string $type, Entry|LocalizedTerm $data = null): ?array
    {
        if (! $data) {
            return Arr::get($this->types(), $type);
        }

        return match ($type) {
            'og' => Arr::get($this->types(), $type),
            'twitter' => Arr::get($this->types(), "twitter.{$data->value('seo_twitter_card', 'summary')}"),
            default => null,
        };
    }

    public function sizeString(string $type): string
    {
        return "{$this->specs($type)['width']} x {$this->specs($type)['height']} pixels";
    }

    public function types(): Collection
    {
        return collect([
            'og' => [
                'type' => 'og',
                'field' => 'seo_og_image',
                'layout' => config('advanced-seo.social_images.presets.open_graph.layout', 'social_images/layout'),
                'template' => config('advanced-seo.social_images.presets.open_graph.template', 'social_images/open_graph'),
                'width' => config('advanced-seo.social_images.presets.open_graph.width', 1200),
                'height' => config('advanced-seo.social_images.presets.open_graph.height', 628),
            ],
            'twitter' => [
                'summary' => [
                    'type' => 'twitter',
                    'field' => 'seo_twitter_image',
                    'layout' => config('advanced-seo.social_images.presets.twitter.summary.layout', 'social_images/layout'),
                    'template' => config('advanced-seo.social_images.presets.twitter.summary.template', 'social_images/twitter_summary'),
                    'width' => config('advanced-seo.social_images.presets.twitter.summary.width', 240),
                    'height' => config('advanced-seo.social_images.presets.twitter.summary.height', 240),
                ],
                'summary_large_image' => [
                    'type' => 'twitter',
                    'field' => 'seo_twitter_image',
                    'layout' => config('advanced-seo.social_images.presets.twitter.summary_large_image.layout', 'social_images/layout'),
                    'template' => config('advanced-seo.social_images.presets.twitter.summary_large_image.template', 'social_images/twitter_summary_large_image'),
                    'width' => config('advanced-seo.social_images.presets.twitter.summary_large_image.width', 1100),
                    'height' => config('advanced-seo.social_images.presets.twitter.summary_large_image.height', 628),
                ],
            ],
        ]);
    }
}
