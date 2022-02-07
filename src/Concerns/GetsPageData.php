<?php

namespace Aerni\AdvancedSeo\Concerns;

use Statamic\Fields\Value;
use Statamic\Facades\Blink;
use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;

trait GetsPageData
{
    use GetsLocale;

    /**
     * Only return values that are not empty and whose keys exists in the Blueprint.
     * This makes sure that we don't return any data of fields that were disabled in the config, e.g. Social Images Generator
     */
    public function getPageData(Collection $context): Collection
    {
        return Blink::once($this->getPageDataCacheKey($context), function () use ($context) {
            return $context->intersectByKeys(OnPageSeoBlueprint::make()->items())
                ->filter(function ($item) {
                    return $item instanceof Value && $item->raw() !== null;
                });
        });
    }

    protected function getPageDataCacheKey(Collection $context): string
    {
        return "advanced-seo::page::{$this->getLocale($context)}";
    }
}
