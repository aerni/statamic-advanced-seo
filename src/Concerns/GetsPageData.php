<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Illuminate\Support\Collection;
use Statamic\Facades\Blink;
use Statamic\Fields\Value;
use Statamic\Tags\Context;

trait GetsPageData
{
    use GetsLocale;

    /**
     * Only return values that are not empty and whose keys exists in the Blueprint.
     * This makes sure that we don't return any data of fields that were disabled in the config, e.g. Social Images Generator
     */
    public function getPageData(Context $context): Collection
    {
        return Blink::once($this->getPageDataCacheKey($context), function () use ($context) {
            return $context->intersectByKeys(OnPageSeoBlueprint::make()->items())
                ->filter(fn ($item) => $item instanceof Value);
        });
    }

    protected function getPageDataCacheKey(Context $context): string
    {
        return "advanced-seo::page::{$this->getLocale($context)}";
    }
}
