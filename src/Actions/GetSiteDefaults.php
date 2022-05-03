<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Actions\EvaluateModelLocale;
use Aerni\AdvancedSeo\Actions\GetAugmentedDefaults;
use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Models\Defaults;
use Illuminate\Support\Collection;
use Statamic\Facades\Blink;
use Statamic\Facades\Site;

class GetSiteDefaults
{
    public static function handle(mixed $data): Collection
    {
        $locale = EvaluateModelLocale::handle($data);

        return Blink::once("advanced-seo::site::{$locale}", function () use ($locale) {
            return Defaults::enabledInType('site')->flatMap(function ($model) use ($locale) {
                return GetAugmentedDefaults::handle(
                    new DefaultsData(
                        type: 'site',
                        handle: $model['handle'],
                        locale: $locale,
                        sites: Site::all()->map->handle(),
                    )
                );
            });
        });
    }
}
