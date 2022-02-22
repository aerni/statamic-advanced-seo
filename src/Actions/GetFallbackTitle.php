<?php

namespace Aerni\AdvancedSeo\Actions;

use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;

class GetFallbackTitle
{
    public static function handle(mixed $data): ?string
    {
        if ($data instanceof Entry || $data instanceof Term) {
            return $data->value('title');
        }

        return null;
    }
}
