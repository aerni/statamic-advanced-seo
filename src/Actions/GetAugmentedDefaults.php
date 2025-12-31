<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;

class GetAugmentedDefaults
{
    public static function handle(DefaultsData $data): Collection
    {
        return Seo::find("{$data->type}::{$data->handle}")
            ?->in($data->locale)
            ?->toAugmentedCollection()
            ?? collect();
    }
}
