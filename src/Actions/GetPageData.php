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
        $fields = OnPageSeoBlueprint::make()->items();

        return match (true) {
            ($model instanceof Context) => $model->intersectByKeys($fields),
            ($model instanceof Entry) => $model->toAugmentedCollection(array_keys($fields)),
            ($model instanceof Term) => $model->toAugmentedCollection(array_keys($fields)),
            default => null,
        };
    }
}
