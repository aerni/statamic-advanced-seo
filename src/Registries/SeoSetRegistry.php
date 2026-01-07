<?php

namespace Aerni\AdvancedSeo\Registries;

use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Data\SeoSetGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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

    public function defaultValue(string $key, mixed $default = null): mixed
    {
        $id = Str::before($key, '.');
        $field = Str::after($key, '.');

        $set = str_contains($id, '::')
            ? $this->find($id)
            : $this->whereType($id)->first();

        return data_get($set?->inDefaultSite()->defaultValues(), $field, $default);
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
            ));
    }

    protected function collectionSeoSets(): Collection
    {
        return Collections::all()
            ->map(fn ($collection) => new \Aerni\AdvancedSeo\Data\SeoSet(
                type: 'collections',
                handle: $collection->handle(),
                title: $collection->title(),
                icon: $collection->icon(),
            ))
            ->sortBy(fn (SeoSet $set) => $set->handle());
    }

    protected function taxonomySeoSets(): Collection
    {
        return Taxonomy::all()
            ->map(fn ($taxonomy) => new \Aerni\AdvancedSeo\Data\SeoSet(
                type: 'taxonomies',
                handle: $taxonomy->handle(),
                title: $taxonomy->title(),
                icon: 'tags',
            ))
            ->sortBy(fn (SeoSet $set) => $set->handle());
    }

    protected function siteSeoSetsDefinition(): array
    {
        return [
            [
                'handle' => 'general',
                'title' => 'General',
                'icon' => 'utilities',
                'enabled' => true,
            ],
            [
                'handle' => 'indexing',
                'title' => 'Indexing',
                'icon' => 'hierarchy',
                'enabled' => true,
            ],
            [
                'handle' => 'social_media',
                'title' => 'Social Media',
                'icon' => 'assets',
                'enabled' => true,
            ],
            [
                'handle' => 'analytics',
                'title' => 'Analytics',
                'icon' => 'money-graph-bar-increase',
                'enabled' => collect(config('advanced-seo.analytics'))
                    ->only('fathom', 'cloudflare_analytics', 'google_tag_manager')
                    ->filter()
                    ->isNotEmpty(),
            ],
            [
                'handle' => 'favicons',
                'title' => 'Favicons',
                'icon' => 'edit-paint-palette',
                'enabled' => config('advanced-seo.favicons.enabled', true),
            ],
        ];
    }
}
