<?php

namespace Aerni\AdvancedSeo\Actions;

use Illuminate\Support\Collection as LaravelCollection;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy;

class EvaluateModelSites
{
    public static function handle(mixed $model): ?LaravelCollection
    {
        return match (true) {
            ($model instanceof Collection) => $model->sites(),
            ($model instanceof Taxonomy) => $model->sites(),
            default => null
        };
    }
}
