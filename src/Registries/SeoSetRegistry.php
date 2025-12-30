<?php

namespace Aerni\AdvancedSeo\Registries;

use Aerni\AdvancedSeo\Blueprints\AnalyticsBlueprint;
use Aerni\AdvancedSeo\Blueprints\ContentDefaultsBlueprint;
use Aerni\AdvancedSeo\Blueprints\FaviconsBlueprint;
use Aerni\AdvancedSeo\Blueprints\GeneralBlueprint;
use Aerni\AdvancedSeo\Blueprints\IndexingBlueprint;
use Aerni\AdvancedSeo\Blueprints\SocialMediaBlueprint;
use Aerni\AdvancedSeo\Contracts\SeoSet;
use Aerni\AdvancedSeo\Data\SeoSetGroup;
use Illuminate\Support\Collection;
use Statamic\Facades\Collection as Collections;
use Statamic\Facades\Taxonomy;

class SeoSetRegistry extends Registry
{
    public function find(string $id): ?SeoSet
    {
        return $this->all()->first(fn (SeoSet $set) => $set->id() === $id);
    }

    public function whereType(string $type): Collection
    {
        return $this->all()
            ->filter(fn (SeoSet $set) => $set->type() === $type)
            ->values();
    }

    public function groups(): Collection
    {
        return $this->all()
            ->groupBy(fn (SeoSet $set) => $set->type())
            ->mapInto(SeoSetGroup::class)
            ->values();
    }

    public function defaultValues(string $id): Collection
    {
        $set = str_contains($id, '::')
            ? $this->find($id)
            : $this->whereType($id)->first();

        return $set?->defaultValues() ?? collect();
    }

    protected function items(): array
    {
        return $this->siteSeoSets()
            ->merge($this->collectionSeoSets())
            ->merge($this->taxonomySeoSets())
            ->values()
            ->all();
    }

    protected function siteSeoSets(): Collection
    {
        return collect($this->siteSeoSetsDefinition())
            ->filter(fn ($config) => $config['enabled'])
            ->map(fn ($site) => new \Aerni\AdvancedSeo\Data\SeoSet(
                type: 'site',
                handle: $site['handle'],
                title: $site['title'],
                icon: $site['icon'],
                blueprint: $site['blueprint'],
                contentFile: $site['contentFile'],
            ))
            ->sortBy('handle');
    }

    protected function collectionSeoSets(): Collection
    {
        return Collections::all()
            ->map(fn ($collection) => new \Aerni\AdvancedSeo\Data\SeoSet(
                type: 'collections',
                handle: $collection->handle(),
                title: $collection->title(),
                icon: $collection->icon(),
                blueprint: ContentDefaultsBlueprint::class,
                contentFile: 'localization.yaml',
                parent: $collection,
            ))
            ->sortBy('handle');
    }

    protected function taxonomySeoSets(): Collection
    {
        return Taxonomy::all()
            ->map(fn ($taxonomy) => new \Aerni\AdvancedSeo\Data\SeoSet(
                type: 'taxonomies',
                handle: $taxonomy->handle(),
                title: $taxonomy->title(),
                icon: 'tags',
                blueprint: ContentDefaultsBlueprint::class,
                contentFile: 'localization.yaml',
                parent: $taxonomy,
            ))
            ->sortBy('handle');
    }

    protected function siteSeoSetsDefinition(): array
    {
        return [
            [
                'handle' => 'general',
                'title' => 'General',
                'blueprint' => GeneralBlueprint::class,
                'icon' => 'utilities',
                'contentFile' => 'general.yaml',
                'enabled' => true,
            ],
            [
                'handle' => 'indexing',
                'title' => 'Indexing',
                'blueprint' => IndexingBlueprint::class,
                'icon' => 'hierarchy',
                'contentFile' => 'indexing.yaml',
                'enabled' => true,
            ],
            [
                'handle' => 'social_media',
                'title' => 'Social Media',
                'blueprint' => SocialMediaBlueprint::class,
                'icon' => 'assets',
                'contentFile' => 'social_media.yaml',
                'enabled' => true,
            ],
            [
                'handle' => 'analytics',
                'title' => 'Analytics',
                'blueprint' => AnalyticsBlueprint::class,
                'icon' => 'money-graph-bar-increase',
                'contentFile' => 'analytics.yaml',
                'enabled' => collect(config('advanced-seo.analytics'))
                    ->only('fathom', 'cloudflare_analytics', 'google_tag_manager')
                    ->filter()
                    ->isNotEmpty(),
            ],
            [
                'handle' => 'favicons',
                'title' => 'Favicons',
                'blueprint' => FaviconsBlueprint::class,
                'icon' => 'edit-paint-palette',
                'contentFile' => 'favicons.yaml',
                'enabled' => config('advanced-seo.favicons.enabled', true),
            ],
        ];
    }
}
