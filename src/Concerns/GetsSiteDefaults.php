<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Models\Defaults;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Blink;
use Statamic\Fields\Value;

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
                return Seo::findOrMake('site', $model['handle'])
                    ->ensureLocalizations(collect($locale))
                    ->in($locale)
                    ->toAugmentedCollection()
                    ->filter(fn ($item) => $item instanceof Value && $item->raw() !== null);
            });
        });
    }

    protected function getSiteCacheKey(string $locale): string
    {
        return "advanced-seo::site::all::$locale";
    }
}
