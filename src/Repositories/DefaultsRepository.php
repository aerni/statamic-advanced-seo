<?php

namespace Aerni\AdvancedSeo\Repositories;

use Illuminate\Support\Collection;

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

    public function all(): Collection
    {
        $defaults = collect([
            [
                'group' => 'site',
                'handle' => 'general',
                'title' => 'General',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\GeneralBlueprint::class,
            ],
            [
                'group' => 'site',
                'handle' => 'indexing',
                'title' => 'Indexing',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\IndexingBlueprint::class,
            ],
            [
                'group' => 'site',
                'handle' => 'social_media',
                'title' => 'Social Media',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\SocialMediaBlueprint::class,
            ],
            [
                'group' => 'content',
                'handle' => 'collections',
                'title' => 'Collections',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\ContentDefaultsBlueprint::class,
            ],
            [
                'group' => 'content',
                'handle' => 'taxonomies',
                'title' => 'Taxonomies',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\ContentDefaultsBlueprint::class,
            ],
        ]);

        if (! empty(array_filter(config('advanced-seo.trackers')))) {
            $defaults->push([
                'group' => 'site',
                'handle' => 'marketing',
                'title' => 'Marketing',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\MarketingBlueprint::class,
            ]);
        }

        if (config('advanced-seo.favicons.enabled', false)) {
            $defaults->push([
                'group' => 'site',
                'handle' => 'favicons',
                'title' => 'Favicons',
                'blueprint' => \Aerni\AdvancedSeo\Blueprints\FaviconsBlueprint::class,
            ]);
        }

        return $defaults;
    }
}
