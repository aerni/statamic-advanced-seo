<?php

namespace Aerni\AdvancedSeo;

use Statamic\Facades\Addon;
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
     */
    public static function edition(): string
    {
        return Addon::get('aerni/advanced-seo')->edition();
    }
}
