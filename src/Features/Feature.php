<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\SeoSets\SeoSet;
use Statamic\Facades\Blink;

abstract class Feature
{
    /**
     * Whether the feature is enabled, optionally for the given context.
     *
     * Per-seo-set contexts run the scope-specific checks so features can gate
     * on the set's own state (e.g. MultiSite on the sites count, Sitemap on
     * the stored toggle). Other contexts fall back to the global availability
     * check, because per-set state doesn't apply.
     */
    public static function enabled(?Context $context = null): bool
    {
        if (static::seoSetFor($context)) {
            // Blink key must start with "advanced-seo::{type}::{handle}::" to be flushed by SeoSet::flushBlink()
            return Blink::once(
                "advanced-seo::{$context->type}::{$context->handle}::features::".static::class."::{$context->scope->value}::{$context->site}",
                fn () => static::available() && static::enabledInScope($context),
            );
        }

        return Blink::once(
            'advanced-seo::features::'.static::class,
            fn () => static::available(),
        );
    }

    /**
     * Whether the feature is globally available (license, config, dependencies).
     */
    abstract protected static function available(): bool;

    /**
     * Whether the feature applies when editing the given seo set's config.
     * Avoid gating on a value that is edited via the config blueprint, or the
     * field that configures this feature will hide itself.
     */
    protected static function enabledInConfig(SeoSet $set): bool
    {
        return true;
    }

    /**
     * Whether the feature applies for the given seo set's localizations.
     * Called for both localization-scope and content-scope contexts.
     */
    protected static function enabledInLocalization(SeoSet $set): bool
    {
        return true;
    }

    private static function seoSetFor(?Context $context): ?SeoSet
    {
        // Site-type configs don't carry the per-set toggles that features gate on, so fall through to available().
        if ($context?->type === 'site') {
            return null;
        }

        return $context?->seoSet();
    }

    private static function enabledInScope(Context $context): bool
    {
        $set = $context->seoSet();

        if ($context->isConfig()) {
            return static::enabledInConfig($set);
        }

        return $set->config()->enabled() && static::enabledInLocalization($set);
    }
}
