<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Context\Context;
use Laravel\Ai\AiServiceProvider;

class Ai extends Feature
{
    public static function enabled(?Context $context = null): bool
    {
        if (! config('advanced-seo.ai.enabled', false)) {
            return false;
        }

        return static::aiSdkConfigured();
    }

    protected static function aiSdkConfigured(): bool
    {
        if (! class_exists(AiServiceProvider::class)) {
            return false;
        }

        $provider = config('advanced-seo.ai.provider') ?? config('ai.default');

        return (bool) config("ai.providers.{$provider}.key");
    }
}
