<?php

namespace Aerni\AdvancedSeo\Concerns;

use Statamic\Facades\Site;
use Statamic\Facades\Blink;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Models\Defaults;
use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Actions\EvaluateModelLocale;
use Aerni\AdvancedSeo\Actions\GetAugmentedDefaults;

trait GetsSiteDefaults
{
    public function getSiteDefaults(mixed $data): Collection
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
