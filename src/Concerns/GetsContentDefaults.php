<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Actions\GetAugmentedDefaults;
use Aerni\AdvancedSeo\Actions\GetDefaultsData;
use Illuminate\Support\Collection;
use Statamic\Facades\Blink;

trait GetsContentDefaults
{
    public function getContentDefaults(mixed $data): Collection
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
