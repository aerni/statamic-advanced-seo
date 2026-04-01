<?php

namespace Aerni\AdvancedSeo;

use Statamic\Licensing\LicenseManager;

class AdvancedSeo
{
    /**
     * Whether the addon is running the pro edition.
     */
    public static function pro(): bool
    {
        return static::edition() === 'pro';
    }
    }

    /**
     * The configured edition of the addon.
     * Reads config directly instead of Addon::get()->edition()
     * so it can be called during service provider registration.
     */
    public static function edition(): string
    {
        return config('statamic.editions.addons.aerni/advanced-seo', 'free');
    }
}
