<?php

namespace Aerni\AdvancedSeo\Actions;

use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Events\TermBlueprintFound;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Taxonomy as TaxonomyFacade;
use Statamic\Fields\Value;
use Statamic\Stache\Query\TermQueryBuilder;
use Statamic\Tags\Context;

class EvaluateModelParent
{
    public static function handle(mixed $data): mixed
    {
        return match (true) {
            ($data instanceof Entry)
                => $data->collection(),
            ($data instanceof Term) // This also handles LocalizedTerm
                => $data->taxonomy(),
            ($data instanceof Context && $data->get('collection') instanceof Value)
                => $data->get('collection')->value(),
            ($data instanceof Context && $data->get('taxonomy') instanceof Value)
                => $data->get('taxonomy')->value(),
            ($data instanceof Context && $data->get('terms') instanceof TermQueryBuilder)
                => TaxonomyFacade::find($data->get('handle')->value()),
            ($data instanceof EntryBlueprintFound)
                => CollectionFacade::find(Str::after($data->blueprint->namespace(), '.')),
            ($data instanceof TermBlueprintFound)
                => TaxonomyFacade::find(Str::after($data->blueprint->namespace(), '.')),
            default => null
        };
    }
}
