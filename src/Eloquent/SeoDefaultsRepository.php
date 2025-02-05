<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\SeoDefaultSet;
use Aerni\AdvancedSeo\Stache\SeoDefaultsRepository as StacheRepository;
use Statamic\Data\DataCollection;

// TODO: Use Blink.
class SeoDefaultsRepository extends StacheRepository
{
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

    public function all(): DataCollection
    {
        $models = app('statamic.eloquent.advanced_seo.model')::all();

        return DataCollection::make($models)->map(function ($model) {
            return app(SeoDefaultSet::class)::fromModel($model);
        });
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
            \Aerni\AdvancedSeo\Contracts\SeoDefaultSet::class => \Aerni\AdvancedSeo\Eloquent\SeoDefaultSet::class
        ];
    }
}
