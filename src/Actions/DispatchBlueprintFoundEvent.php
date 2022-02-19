<?php

namespace Aerni\AdvancedSeo\Actions;

use Statamic\Contracts\Entries\Entry;
use Statamic\Taxonomies\LocalizedTerm;
use Statamic\Events\TermBlueprintFound;
use Statamic\Events\EntryBlueprintFound;

class DispatchBlueprintFoundEvent
{
    public static function handle(mixed $data): void
    {
        if ($data instanceof Entry) {
            EntryBlueprintFound::dispatch($data->blueprint(), $data);
        }

        if ($data instanceof LocalizedTerm) {
            TermBlueprintFound::dispatch($data->blueprint(), $data);
        }
    }
}
