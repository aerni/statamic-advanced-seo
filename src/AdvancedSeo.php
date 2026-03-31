<?php

namespace Aerni\AdvancedSeo;

use Statamic\Facades\Addon;
use Statamic\Licensing\LicenseManager;

class AdvancedSeo
{
    /**
     * Whether pro features should be available.
     * Returns true if the addon is on the pro edition,
     * or if the site is running on a test/local domain (trial mode).
     */
    public static function pro(): bool
    {
        return static::edition() === 'pro'
            || app(LicenseManager::class)->isOnTestDomain();
    }

    /**
     * The configured edition of the addon.
     */
    public static function edition(): string
    {
        return Addon::get('aerni/advanced-seo')->edition();
    }
}
