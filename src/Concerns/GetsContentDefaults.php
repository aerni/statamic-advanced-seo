<?php

namespace Aerni\AdvancedSeo\Concerns;

use Statamic\Facades\Blink;
use Aerni\AdvancedSeo\Actions\GetDefaultsData;
use Aerni\AdvancedSeo\Actions\GetAugmentedDefaults;
use Illuminate\Support\Collection;

trait GetsContentDefaults
{
    public function getContentDefaults(mixed $data): ?Collection
    {
        if (! $data = GetDefaultsData::handle($data)) {
            return null;
        }

        return Blink::once(
            "advanced-seo::{$data->type}::{$data->handle}::{$data->locale}",
            fn () => GetAugmentedDefaults::handle($data)
        );
    }
}
