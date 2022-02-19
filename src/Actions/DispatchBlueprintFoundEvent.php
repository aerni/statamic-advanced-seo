<?php

namespace Aerni\AdvancedSeo\Actions;

use Statamic\Contracts\Entries\Entry;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Events\TermBlueprintFound;
use Statamic\Taxonomies\LocalizedTerm;

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
