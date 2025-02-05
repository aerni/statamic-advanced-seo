<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\SeoDefaultSet;
use Aerni\AdvancedSeo\Stache\SeoDefaultsRepository as StacheRepository;
use Illuminate\Support\Collection;
use Statamic\Data\DataCollection;

// TODO: Use Blink.
class SeoDefaultsRepository extends StacheRepository
{
    // TODO: Why is this called on every request even if no SEO data is queried?
    public function find(string $type, string $handle): ?SeoDefaultSet
    {
        $model = app('statamic.eloquent.advanced_seo.model')::query()
            ->whereType($type)
            ->whereHandle($handle)
            ->first();

        if (! $model) {
            return null;
        }

        return app(SeoDefaultSet::class)->fromModel($model);
    }

    public function all(): Collection
    {
        return app('statamic.eloquent.advanced_seo.model')::all()
            ->map(fn ($model) => app(SeoDefaultSet::class)::fromModel($model));
    }

    public function allOfType(string $type): DataCollection
    {
        $models = app('statamic.eloquent.advanced_seo.model')::query()
            ->whereType($type)
            ->get()
            ->map(fn ($model) => app(SeoDefaultSet::class)::fromModel($model));

        return DataCollection::make($models);
    }

    public function save(SeoDefaultSet $set): self
    {
        $model = $set->toModel();

        $model->save();

        $set->model($model->fresh());

        return $this;
    }

    public function delete(SeoDefaultSet $set): bool
    {
        $set->model()->delete();

        return true;
    }

    public static function bindings(): array
    {
        return [
            \Aerni\AdvancedSeo\Contracts\SeoDefaultSet::class => \Aerni\AdvancedSeo\Eloquent\SeoDefaultSet::class,
        ];
    }
}
