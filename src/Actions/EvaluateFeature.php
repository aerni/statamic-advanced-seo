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

        return Blink::once("advanced-seo::features::{$feature}::{$context->id()}", fn () => $feature::enabled($context));
    }
}
