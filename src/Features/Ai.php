<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\AdvancedSeo;
use Aerni\AdvancedSeo\Context\Context;
use Statamic\Console\Processes\Composer;

class Ai extends Feature
{
    public static function enabled(?Context $context = null): bool
    {
        if (! AdvancedSeo::pro()) {
            return false;
        }

        if (! config('advanced-seo.ai.enabled', false)) {
            return false;
        }

        if (! static::aiSdkConfigured()) {
            return false;
        }

        if (! $context) {
            return true;
        }

        /* Always show toggle in the config */
        if ($context->isConfig()) {
            return true;
        }

        if (! $context->seoSet()->enabled()) {
            return false;
        }

        return $context->seoSet()->config()->value('ai');
    }

    protected static function aiSdkConfigured(): bool
    {
        if (! app(Composer::class)->isInstalled('laravel/ai')) {
            return false;
        }

        $provider = config('advanced-seo.ai.provider') ?? config('ai.default');

        return (bool) config("ai.providers.{$provider}.key");
    }
}
