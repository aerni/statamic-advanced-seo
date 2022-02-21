<?php

namespace Aerni\AdvancedSeo\Concerns;

use Statamic\Fields\Value;
use Statamic\Facades\Blink;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Aerni\AdvancedSeo\Models\Defaults;
use Statamic\Contracts\Taxonomies\Term;
use Aerni\AdvancedSeo\Actions\GetAugmentedDefaults;

trait GetsSiteDefaults
{
    use GetsLocale;

    /**
     * Get the augmented site defaults and filter out any values that shouldn't be there,
     * like features that were disabled in the config.
     */
    public function getSiteDefaults(Entry|Term|Collection $data = null): Collection
    {
        $locale = $this->getLocale($data);

        return Blink::once($this->getSiteCacheKey($locale), function () use ($locale) {
            return Defaults::enabledInGroup('site')->flatMap(function ($model) use ($locale) {
                return GetAugmentedDefaults::handle('site', $model['handle'], $locale);
            });
        });
    }

    protected function getSiteCacheKey(string $locale): string
    {
        return "advanced-seo::site::all::$locale";
    }
}
