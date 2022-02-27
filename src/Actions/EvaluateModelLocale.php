<?php

namespace Aerni\AdvancedSeo\Actions;

use Statamic\Statamic;
use Statamic\Facades\Site;
use Statamic\Tags\Context;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Events\TermBlueprintFound;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy;

class EvaluateModelLocale
{
    public static function handle(mixed $model): ?string
    {
        return match (true) {
            ($model instanceof Entry)
                => $model->locale(),
            ($model instanceof Term) // This also handles LocalizedTerm
                => basename(request()->path()),
            ($model instanceof Context)
                => $model->get('site')->handle(),
            ($model instanceof EntryBlueprintFound)
                => basename(request()->path()),
            ($model instanceof TermBlueprintFound)
                => basename(request()->path()),
            default => null
        };
    }
}
