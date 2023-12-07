<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Models\Defaults;
use Illuminate\Support\Collection;
use Statamic\Facades\Blink;
use Statamic\Facades\Site;
use Statamic\Fields\Value;

class GetSiteDefaults
{
    public static function handle(mixed $data): Collection
    {
        if (! $locale = EvaluateModelLocale::handle($data)) {
            return collect();
        }

        /**
         * TODO: Instead of using the overriding code at the bottom, we might be able to refactor this to something similar to the GetPageData action.
         * Instead of augmenting all the default values upfront, we could get the blueprint of each site default and then add the values to each blueprint field.
         */
        return Blink::once("advanced-seo::site::{$locale}", function () use ($locale, $data) {
            $siteDefaults = Defaults::enabledInType('site')
                ->flatMap(fn ($model) => GetAugmentedDefaults::handle(
                    new DefaultsData(
                        type: 'site',
                        handle: $model['handle'],
                        locale: $locale,
                        sites: Site::all()->map->handle(),
                    )
                ));

            /**
             * Allow overriding site defaults by matching key in the data.
             * This is useful if you want to override the site default when working with custom views.
             * We need to create a new value object because we can't simply change the `value` in the existing object.
             */
            $overrides = $siteDefaults->intersectByKeys($data)
                ->map(fn ($originalValue, $key) => new Value(
                    value: ($value = $data->get($key)) instanceof Value ? $value->raw() : $value,
                    handle: $originalValue->handle(),
                    fieldtype: $originalValue->fieldtype(),
                    augmentable: $originalValue->augmentable()
                ));

            return $siteDefaults->merge($overrides);
        });
    }
}
