<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Blink;

class GetOnPageSeoData
{
    public static function handle(Entry $model): Collection
    {
        $fields = array_keys(OnPageSeoBlueprint::make()->items());

        return Blink::once(
            "advanced-seo::model::{$model->id()}",
            fn () => $model->toAugmentedCollection($fields)
        );
    }
}
