<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Actions\GetDefaultsData;
use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Illuminate\Support\Collection;
use Statamic\Facades\Blink;
use Statamic\Tags\Context;

class GetPageData
{
    public static function handle(Context $context): Collection
    {
        if (! $data = GetDefaultsData::handle($context)) {
            return collect();
        }

        return Blink::once(
            "advanced-seo::page::{$data->locale}",
            fn () => $context->intersectByKeys(OnPageSeoBlueprint::make()->data($data)->items())
        );
    }
}
