<?php

namespace Aerni\AdvancedSeo\Actions;

use Statamic\Statamic;
use Statamic\Tags\Context;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Events\TermBlueprintFound;
use Aerni\AdvancedSeo\Data\DefaultsData;
use Statamic\Events\EntryBlueprintFound;

class EvaluateModelLocale
{
    public static function handle(mixed $model): ?string
    {
        return match (true) {
            ($model instanceof Entry)
                => $model->locale(),
            ($model instanceof Term) // This also handles LocalizedTerm
                => Statamic::isCpRoute() ? basename(request()->path()) : $model->locale(),
            ($model instanceof Context)
                => $model->get('site')->handle(),
            ($model instanceof EntryBlueprintFound)
                => basename(request()->path()),
            ($model instanceof TermBlueprintFound)
                => basename(request()->path()),
            ($model instanceof DefaultsData)
                => $model->locale,
            default => null
        };
    }
}
