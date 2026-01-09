<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Statamic\Facades\Blink;
use Statamic\Fields\Value;
use Statamic\Tags\Context as TagsContext;

class GetSiteDefaults
{
    public static function handle(mixed $model): Collection
    {
        if (! $site = Context::from($model)?->site) {
            return collect();
        }

        return Blink::once("advanced-seo::site::{$site}", function () use ($site, $model) {
            $siteDefaults = Seo::whereType('site')
                ->flatMap(fn (SeoSet $set) => $set->in($site)?->toAugmentedCollection() ?? collect());

            /**
             * TODO: Instead of merging the overrides, we might be able to refactor this to something similar to the GetPageData action.
             * Instead of augmenting all the default values upfront, we could get the blueprint of each site default and then add the values to each blueprint field.
             */
            if ($model instanceof TagsContext) {
                return self::mergeViewOverrides($siteDefaults, $model);
            }

            return $siteDefaults;
        });
    }

    /**
     * Allow overriding site defaults by matching key in the data.
     * This is useful if you want to override the site default when working with custom views.
     * We need to create a new value object because we can't simply change the `value` in the existing object.
     */
    protected static function mergeViewOverrides(Collection $siteDefaults, Collection $overrides): Collection
    {
        $overrides = $siteDefaults
            ->intersectByKeys($overrides)
            ->map(fn ($originalValue, $key) => new Value(
                value: ($value = $overrides->get($key)) instanceof Value ? $value->raw() : $value,
                handle: $originalValue->handle(),
                fieldtype: $originalValue->fieldtype(),
                augmentable: $originalValue->augmentable()
            ));

        return $siteDefaults->merge($overrides);
    }
}
