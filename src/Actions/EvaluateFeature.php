<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Data\DefaultsData;
use Statamic\Facades\Blink;

class EvaluateFeature
{
    public static function handle(string $feature, ?DefaultsData $data = null): bool
    {
        if (! $data) {
            return Blink::once("advanced-seo::features::{$feature}", fn () => $feature::enabled());
        }

        // Structure cache key with set ID first for efficient bulk clearing
        $cacheKey = "advanced-seo::{$data->type}::{$data->handle}::features::{$feature}::{$data->locale}";

        return Blink::once($cacheKey, fn () => $feature::enabled($data));
    }
}
