<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Fields\Value;

class GetPageData
{
    public static function handle(mixed $model): Collection
    {
        /**
         * We only want to return data of enabled features.
         * This ensures that we don't return any values of conditionally hidden fields.
         * This would typically happen when a feature like the social images generator has been disabled.
         */
        $fields = OnPageSeoBlueprint::resolve($model)->fields()->all();

        if ($model instanceof Entry || $model instanceof Term) {
            return $model->toAugmentedCollection($fields->keys()->toArray());
        }

        return $model->intersectByKeys($fields)
            ->map(
                fn ($value, $field) => $value instanceof Value
                ? $value
                : $fields->get($field)->setValue($value)->augment()->value()
            );
    }
}
