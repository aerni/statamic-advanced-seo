<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Tags\Context;

class GetPageData
{
    public static function handle(mixed $model): ?Collection
    {
        /**
         * We only want to return data of enabled features.
         * This ensures that we don't return any values of conditionally hidden fields.
         * This would typically happen when a feature like the social images generator has been disabled.
         */
        $fields = OnPageSeoBlueprint::make()
            ->data(GetDefaultsData::handle($model))
            ->items();

        return match (true) {
            ($model instanceof Context) => $model->intersectByKeys($fields),
            ($model instanceof Entry) => $model->toAugmentedCollection(array_keys($fields)),
            ($model instanceof Term) => $model->toAugmentedCollection(array_keys($fields)),
            default => null,
        };
    }
}
