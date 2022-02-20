<?php

namespace Aerni\AdvancedSeo\Repositories;

use Illuminate\Support\Collection;
use Statamic\Facades\Blink;
use Statamic\Facades\YAML;

class DefaultsRepository
{
    public function site(): Collection
    {
        return $this->all()->filter(function ($item) {
            return $item['group'] === 'site';
        })->values();
    }

    public function content(): Collection
    {
        return $this->all()->filter(function ($item) {
            return $item['group'] === 'content';
        })->values();
    }

    public function groups(): Collection
    {
        return $this->all()->groupBy('group')->keys();
    }

    public function data(string $key): Collection
    {
        return Blink::once("advanced-seo::defaults::$key", function () use ($key) {
            $path = $this->all()->firstWhere('handle', $key)['data'];
            $data = YAML::file($path)->parse();

            return collect($data);
        });
    }

    public function all(): Collection
    {
        $defaults = collect([
            [
                'group' => 'site',
                'handle' => 'general',
                'title' => 'General',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\GeneralBlueprint::class,
                'data' => __DIR__.'/../../content/general.yaml',
            ],
            [
                'group' => 'site',
                'handle' => 'indexing',
                'title' => 'Indexing',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\IndexingBlueprint::class,
                'data' => __DIR__.'/../../content/indexing.yaml',
            ],
            [
                'group' => 'site',
                'handle' => 'social_media',
                'title' => 'Social Media',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\SocialMediaBlueprint::class,
                'data' => __DIR__.'/../../content/social_media.yaml',
            ],
            [
                'group' => 'content',
                'handle' => 'collections',
                'title' => 'Collections',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\ContentDefaultsBlueprint::class,
                'data' => __DIR__.'/../../content/content.yaml',
            ],
            [
                'group' => 'content',
                'handle' => 'taxonomies',
                'title' => 'Taxonomies',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\ContentDefaultsBlueprint::class,
                'data' => __DIR__.'/../../content/content.yaml',
            ],
        ]);

        if (! empty(array_filter(config('advanced-seo.analytics')))) {
            $defaults->push([
                'group' => 'site',
                'handle' => 'analytics',
                'title' => 'Analytics',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\AnalyticsBlueprint::class,
                'data' => __DIR__.'/../../content/analytics.yaml',
            ]);
        }

        if (config('advanced-seo.favicons.enabled', false)) {
            $defaults->push([
                'group' => 'site',
                'handle' => 'favicons',
                'title' => 'Favicons',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\FaviconsBlueprint::class,
                'data' => __DIR__.'/../../content/favicons.yaml',
            ]);
        }

        return $defaults;
    }
}
