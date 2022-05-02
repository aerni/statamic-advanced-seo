<?php

namespace Aerni\AdvancedSeo\Actions;

use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Events\TermBlueprintFound;
use Aerni\AdvancedSeo\Data\DefaultsData;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy;

class EvaluateModelType
{
    public static function handle(mixed $model): ?string
    {
        return match (true) {
            ($model instanceof Collection) => 'collections',
            ($model instanceof Entry) => 'collections',
            ($model instanceof EntryBlueprintFound) => 'collections',
            ($model instanceof Taxonomy) => 'taxonomies',
            ($model instanceof Term) => 'taxonomies',
            ($model instanceof TermBlueprintFound) => 'taxonomies',
            ($model instanceof DefaultsData) => $model->type,
            default => null
        };
    }
}
