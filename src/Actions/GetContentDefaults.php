<?php

namespace Aerni\AdvancedSeo\Actions;

use Illuminate\Support\Collection;
use Statamic\Facades\Blink;

class GetContentDefaults
{
    public static function handle(mixed $data): Collection
    {
        if (! $data = GetDefaultsData::handle($data)) {
            return collect();
        }

        return Blink::once(
            "advanced-seo::{$data->type}::{$data->handle}::{$data->locale}",
            fn () => GetAugmentedDefaults::handle($data)
        );
    }
}
