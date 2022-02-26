<?php

namespace Aerni\AdvancedSeo\Actions;

use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Events\TermBlueprintFound;
use Statamic\Facades\Site;
use Statamic\Statamic;
use Statamic\Tags\Context;

class EvaluateModelLocale
{
    public static function handle(mixed $model): ?string
    {
        return match (true) {
            ($model instanceof Entry)
                => $model->locale(),
            ($model instanceof Term) // This also handles LocalizedTerm
                => Statamic::isCpRoute() ? basename(request()->path()) : Site::current()->handle(), // TODO: Do we really need this frontend fallback? Isn't this handled by the Context case?
            ($model instanceof Context && $model->get('collection') instanceof Collection)
                => $model->get('locale'),
            ($model instanceof Context && $model->get('taxonomy') instanceof Taxonomy)
                => Site::current()->handle(),
            ($model instanceof EntryBlueprintFound)
                => basename(request()->path()),
            ($model instanceof TermBlueprintFound)
                => basename(request()->path()),
            default => null
        };
    }
}
