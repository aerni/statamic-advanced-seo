<?php

namespace Aerni\AdvancedSeo\Registries;

use Aerni\AdvancedSeo\SeoSets\SeoSet;
use Aerni\AdvancedSeo\SeoSets\SeoSetGroup;
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
        return collect([
            new SeoSet(
                type: 'site',
                handle: 'defaults',
                title: __('advanced-seo::messages.site_defaults'),
                icon: 'utilities',
            ),
        ]);
    }

    protected function collectionSeoSets(): Collection
    {
        return Collections::all()
            ->map(fn ($collection) => new SeoSet(
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
            ->map(fn ($taxonomy) => new SeoSet(
                type: 'taxonomies',
                handle: $taxonomy->handle(),
                title: $taxonomy->title(),
                icon: 'taxonomies',
            ))
            ->sortBy(fn (SeoSet $set) => $set->handle());
    }
}
