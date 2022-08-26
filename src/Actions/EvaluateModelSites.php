<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Illuminate\Support\Collection as LaravelCollection;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy;

class EvaluateModelSites
{
    public static function handle(mixed $model): ?LaravelCollection
    {
        return match (true) {
            ($model instanceof SeoDefaultSet) => $model->localizations()->map->locale()->values(),
            ($model instanceof Collection) => $model->sites(),
            ($model instanceof Taxonomy) => $model->sites(),
            ($model instanceof DefaultsData) => $model->sites,
            default => null
        };
    }
}
