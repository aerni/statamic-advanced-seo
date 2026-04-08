<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\AdvancedSeo;
use Aerni\AdvancedSeo\Contracts\SeoSetConfig;
use Statamic\Console\Processes\Composer;

class Ai extends Feature
{
    protected static function available(): bool
    {
        if (! AdvancedSeo::pro()) {
            return false;
        }

        if (! config('advanced-seo.ai.enabled', false)) {
            return false;
        }

        if (! app(Composer::class)->isInstalled('laravel/ai')) {
            return false;
        }

        $provider = config('advanced-seo.ai.provider') ?? config('ai.default');

        return (bool) config("ai.providers.{$provider}.key");
    }

    protected static function enabledInConfig(SeoSetConfig $config): bool
    {
        return $config->value('ai');
    }
}
