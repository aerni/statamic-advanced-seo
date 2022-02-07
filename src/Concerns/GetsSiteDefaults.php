<?php

namespace Aerni\AdvancedSeo\Concerns;

use Statamic\Fields\Value;
use Statamic\Facades\Blink;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;

trait GetsSiteDefaults
{
    use GetsLocale;

    public function getSiteDefaults(Entry|Term|array $data): Collection
    {
        return Blink::once($this->getSiteCacheKey($data), function () use ($data) {
            return Seo::allOfType('site')->flatMap(function ($defaults) use ($data) {
                return $defaults->in($this->getLocale($data))?->toAugmentedArray();
            })->filter(function ($item) {
                // Only return values that have a corresponding field in the blueprint.
                return $item instanceof Value;
            });
        });
    }

    protected function getSiteCacheKey(Entry|Term|array $data): string
    {
        return "advanced-seo::site::all::{$this->getLocale($data)}";
    }
}
