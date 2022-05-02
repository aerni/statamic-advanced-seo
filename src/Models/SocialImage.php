<?php

namespace Aerni\AdvancedSeo\Models;

use Illuminate\Support\Collection;

class SocialImage extends Model
{
    public static $types = ['open_graph', 'twitter_summary', 'twitter_summary_large_image'];

    protected static function getRows(): array
    {
        return [
            'open_graph' => [
                'group' => 'open_graph',
                'type' => 'open_graph',
                'layout' => 'social_images/layout',
                'templates' => SocialImageTheme::templatesOfType('open_graph'),
                'width' => config('advanced-seo.social_images.presets.open_graph.width', 1200),
                'height' => config('advanced-seo.social_images.presets.open_graph.height', 628),
            ],
            'twitter_summary' => [
                'group' => 'twitter',
                'type' => 'twitter_summary',
                'layout' => 'social_images/layout',
                'templates' => SocialImageTheme::templatesOfType('twitter_summary'),
                'width' => config('advanced-seo.social_images.presets.twitter_summary.width', 240),
                'height' => config('advanced-seo.social_images.presets.twitter_summary.height', 240),
            ],
            'twitter_summary_large_image' => [
                'group' => 'twitter',
                'type' => 'twitter_summary_large_image',
                'layout' => 'social_images/layout',
                'templates' => SocialImageTheme::templatesOfType('twitter_summary_large_image'),
                'width' => config('advanced-seo.social_images.presets.twitter_summary_large_image.width', 1100),
                'height' => config('advanced-seo.social_images.presets.twitter_summary_large_image.height', 628),
            ],
        ];
    }

    protected static function all(): Collection
    {
        return static::$rows;
    }

    protected static function groups(): Collection
    {
        return collect(static::$rows)->groupBy('group');
    }
}
