<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;

class GetAugmentedDefaults
{
    public static function handle(DefaultsData $data): Collection
    {
        return Seo::findOrMake($data->type, $data->handle)
            ->ensureLocalizations($data->sites)
            ->in($data->locale)
            ->toAugmentedCollection();
    }
}
