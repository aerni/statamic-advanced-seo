<?php

namespace Aerni\AdvancedSeo\Models;

use Illuminate\Support\Collection;

class SocialImage extends Model
{
    public static $types = ['open_graph', 'twitter_summary', 'twitter_summary_large'];

    protected static function getRows(): array
    {
        return [
            'og' => [
                'type' => 'og',
                'layout' => 'social_images/layout',
                'templates' => SocialImageTheme::templatesOfType('open_graph'),
                'width' => config('advanced-seo.social_images.presets.open_graph.width', 1200),
                'height' => config('advanced-seo.social_images.presets.open_graph.height', 628),
            ],
            'twitter' => [
                'summary' => [
                    'type' => 'twitter',
                    'layout' => 'social_images/layout',
                    'templates' => SocialImageTheme::templatesOfType('twitter_summary'),
                    'width' => config('advanced-seo.social_images.presets.twitter_summary.width', 240),
                    'height' => config('advanced-seo.social_images.presets.twitter_summary.height', 240),
                ],
                'summary_large_image' => [
                    'type' => 'twitter',
                    'layout' => 'social_images/layout',
                    'templates' => SocialImageTheme::templatesOfType('twitter_summary_large'),
                    'width' => config('advanced-seo.social_images.presets.twitter_summary_large_image.width', 1100),
                    'height' => config('advanced-seo.social_images.presets.twitter_summary_large_image.height', 628),
                ],
            ],
        ];
    }

    protected static function all(): Collection
    {
        return static::$rows;
    }
}
