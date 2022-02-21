<?php

namespace Aerni\AdvancedSeo\Models;

use Statamic\Facades\YAML;
use Statamic\Facades\Blink;
use Illuminate\Support\Collection;

class Defaults extends Model
{
    protected static function getRows(): array
    {
        return [
            [
                'group' => 'site',
                'type' => 'site',
                'handle' => 'general',
                'title' => 'General',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\GeneralBlueprint::class,
                'data' => __DIR__.'/../../content/general.yaml',
                'enabled' => true,
            ],
            [
                'group' => 'site',
                'type' => 'site',
                'handle' => 'indexing',
                'title' => 'Indexing',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\IndexingBlueprint::class,
                'data' => __DIR__.'/../../content/indexing.yaml',
                'enabled' => true,
            ],
            [
                'group' => 'site',
                'type' => 'site',
                'handle' => 'social_media',
                'title' => 'Social Media',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\SocialMediaBlueprint::class,
                'data' => __DIR__.'/../../content/social_media.yaml',
                'enabled' => true,
            ],
            [
                'group' => 'site',
                'type' => 'site',
                'handle' => 'analytics',
                'title' => 'Analytics',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\AnalyticsBlueprint::class,
                'data' => __DIR__.'/../../content/analytics.yaml',
                'enabled' => ! empty(array_filter(config('advanced-seo.analytics')))
            ],
            [
                'group' => 'site',
                'type' => 'site',
                'handle' => 'favicons',
                'title' => 'Favicons',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\FaviconsBlueprint::class,
                'data' => __DIR__.'/../../content/favicons.yaml',
                'enabled' => config('advanced-seo.favicons.enabled', false)
            ],
            [
                'group' => 'content',
                'type' => 'collections',
                'handle' => 'collections',
                'title' => 'Collections',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\ContentDefaultsBlueprint::class,
                'data' => __DIR__.'/../../content/content.yaml',
                'enabled' => true,
            ],
            [
                'group' => 'content',
                'type' => 'taxonomies',
                'handle' => 'taxonomies',
                'title' => 'Taxonomies',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\ContentDefaultsBlueprint::class,
                'data' => __DIR__.'/../../content/content.yaml',
                'enabled' => true,
            ],
        ];
    }

    // TODO: Rename to groupIsSite
    protected static function site(): Collection
    {
        return static::$rows->groupBy('group')->get('site');
    }

    // TODO: Rename to groupIsContent
    protected static function content(): Collection
    {
        return static::$rows->groupBy('group')->get('content');
    }

    protected static function groups(): Collection
    {
        return static::$rows->groupBy('group')->keys();
    }

    // TODO: Rename to dataOf
    protected static function data(string $handle): Collection
    {
        return Blink::once("advanced-seo::defaults::data::$handle", function () use ($handle) {
            $path = static::$rows->firstWhere('handle', $handle)['data'];
            return collect(YAML::file($path)->parse());
        });
    }

    // TODO: Apply this to the views and policy. Anywhere else?
    protected static function enabled(): Collection
    {
        return static::$rows->where('enabled', true);
    }
}
