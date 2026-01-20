<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Features\Feature;
use Statamic\Facades\Blink;

class EvaluateFeature
{
    /**
     * @param  class-string<Feature>  $feature
     */
    public static function handle(string $feature, ?Context $context = null): bool
    {
        if (! $context) {
            return Blink::once("advanced-seo::features::{$feature}", fn () => $feature::enabled());
        }

        // Key must start with "advanced-seo::{type}::{handle}::" to be flushed by SeoSet::flushBlink()
        return Blink::once("advanced-seo::{$context->type}::{$context->handle}::features::{$feature}::{$context->scope->value}::{$context->site}", fn () => $feature::enabled($context));
    }
}
