<?php

namespace Aerni\AdvancedSeo\Concerns;

use Statamic\Facades\Blink;
use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Actions\GetAugmentedDefaults;
use Aerni\AdvancedSeo\Actions\GetDefaultsData;

trait GetsContentDefaults
{
    // TODO: Add a method to get augmented defaults and another to get unaugmented defaults.

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
