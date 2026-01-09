<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Context\Context;
use Statamic\Facades\Blink;

class EvaluateFeature
{
    public static function handle(string $feature, ?Context $context = null): bool
    {
        if (! $context) {
            return Blink::once("advanced-seo::features::{$feature}", fn () => $feature::enabled());
        }

        // Structure cache key with set ID first for efficient bulk clearing
        $cacheKey = "advanced-seo::{$context->type}::{$context->handle}::features::{$feature}::{$context->site}";

        return Blink::once($cacheKey, fn () => $feature::enabled($context));
    }
}
