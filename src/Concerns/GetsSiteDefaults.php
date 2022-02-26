<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Actions\GetAugmentedDefaults;
use Aerni\AdvancedSeo\Models\Defaults;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Blink;
use Statamic\Facades\Site;

trait GetsSiteDefaults
{
    use GetsLocale;

    /**
     * Get the augmented site defaults and filter out any values that shouldn't be there,
     * like features that were disabled in the config.
     */
    public function getSiteDefaults(Entry|Term|Collection $data = null): Collection
    {
        // TODO: Fix the site defaults.
        $locale = $this->getLocale($data);

        return Blink::once($this->getSiteCacheKey($locale), function () use ($locale) {
            return Defaults::enabledInType('site')->flatMap(function ($model) use ($locale) {
                return GetAugmentedDefaults::handle('site', $model['handle'], $locale, Site::all()->map->handle());
            });
        });
    }

    // public function getSiteDefaults(mixed $data): Collection
    // {
    //     $locale = EvaluateModelLocale::handle($data);

    //     return Blink::once("advanced-seo::site::all::$locale", function () use ($locale) {
    //         return Defaults::enabledInType('site')->flatMap(function ($model) use ($locale) {
    //             return GetAugmentedDefaults::handle(new DefaultsData(
    //                 type: 'site',
    //                 handle: $model['handle'],
    //                 locale: $locale,
    //                 sites: Site::all()->map->handle(),
    //             ));
    //         });
    //     });
    // }

    protected function getSiteCacheKey(string $locale): string
    {
        return "advanced-seo::site::all::$locale";
    }
}
