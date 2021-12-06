<?php

namespace Aerni\AdvancedSeo\Traits;

use Statamic\Fields\Value;
use Statamic\Facades\Blink;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;

trait GetsSiteDefaults
{
    use GetsLocale;

    public function getSiteDefaults($data): Collection
    {
        return Blink::once($this->getSiteCacheKey($data), function () use ($data) {
            return Seo::allOfType('site')->flatMap(function ($defaults) use ($data) {
                return $defaults->in($this->getLocale($data))->toAugmentedArray();
            })->filter(function ($item) {
                return $item instanceof Value;
            });
        });
    }

    protected function getSiteCacheKey($data): string
    {
        return "advanced-seo::site::all::{$this->getLocale($data)}";
    }
}
