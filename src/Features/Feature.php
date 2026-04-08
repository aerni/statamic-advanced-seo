<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Contracts\SeoSetConfig;
use Statamic\Facades\Blink;

abstract class Feature
{
    /**
     * Whether the feature is enabled, optionally for the given context.
     */
    public static function enabled(?Context $context = null): bool
    {
        $config = static::configFor($context);

        if ($config === null) {
            return Blink::once('advanced-seo::features::'.static::class, fn () => static::available());
        }

        // Key must start with "advanced-seo::{type}::{handle}::" to be flushed by SeoSet::flushBlink()
        return Blink::once(
            "advanced-seo::{$context->type}::{$context->handle}::features::".static::class."::{$context->scope->value}::{$context->site}",
            fn () => static::available() && $config->enabled() && static::enabledInConfig($config)
        );
    }

    /**
     * Whether the feature is globally available (license, config, dependencies).
     */
    abstract protected static function available(): bool;

    /**
     * Whether the feature's SeoSet-level toggle is on for the given config.
     */
    protected static function enabledInConfig(SeoSetConfig $config): bool
    {
        return true;
    }

    private static function configFor(?Context $context): ?SeoSetConfig
    {
        if ($context === null || $context->isConfig()) {
            return null;
        }

        return $context->seoSet()?->config();
    }
}
