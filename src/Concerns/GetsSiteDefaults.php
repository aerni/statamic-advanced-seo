<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Actions\GetAugmentedDefaults;
use Aerni\AdvancedSeo\Models\Defaults;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Blink;

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
