<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Data\DefaultsData;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;

class EvaluateModelHandle
{
    public static function handle(mixed $model): ?string
    {
        return match (true) {
            ($model instanceof Collection) => $model->handle(),
            ($model instanceof Entry) => $model->collection()->handle(),
            ($model instanceof Taxonomy) => $model->handle(),
            ($model instanceof Term) => $model->taxonomy()->handle(),
            ($model instanceof DefaultsData) => $model->handle,
            default => null
        };
    }
}
