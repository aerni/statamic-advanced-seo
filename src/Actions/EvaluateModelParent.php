<?php

namespace Aerni\AdvancedSeo\Actions;

use Statamic\Tags\Context;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Taxonomies\LocalizedTerm;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Events\TermBlueprintFound;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Facades\Taxonomy as TaxonomyFacade;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Stache\Query\TermQueryBuilder;

class EvaluateModelParent
{
    public static function handle(mixed $data): mixed
    {
        return match (true) {
            ($data instanceof Entry)
                => $data->collection(),
            ($data instanceof Term) // This also handles LocalizedTerm
                => $data->taxonomy(),
            ($data instanceof Context && $data->get('collection') instanceof Collection)
                => $data->get('collection'),
            ($data instanceof Context && $data->get('taxonomy') instanceof Taxonomy)
                => $data->get('taxonomy'),
            ($data instanceof Context && $data->get('terms') instanceof TermQueryBuilder)
                => TaxonomyFacade::find($data->get('handle')),
            ($data instanceof EntryBlueprintFound)
                => CollectionFacade::find(Str::after($data->blueprint->namespace(), '.')),
            ($data instanceof TermBlueprintFound)
                => TaxonomyFacade::find(Str::after($data->blueprint->namespace(), '.')),
            default => null
        };
    }
}
