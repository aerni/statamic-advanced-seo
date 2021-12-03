<?php

namespace Aerni\AdvancedSeo\Traits;

use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Statamic\Fields\Value;

trait GetsSiteDefaults
{
    use GetsLocale;

    public function getSiteDefaults($data): Collection
    {
        return Seo::allOfType('site')->flatMap(function ($defaults) use ($data) {
            return $defaults->in($this->getLocale($data))->toAugmentedArray();
        })->filter(function ($item) {
            return $item instanceof Value;
        });
    }
}
