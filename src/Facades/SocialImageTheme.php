<?php

namespace Aerni\AdvancedSeo\Facades;

use Aerni\AdvancedSeo\Registries\SocialImageThemeRegistry;
use Illuminate\Support\Facades\Facade;

class SocialImageTheme extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SocialImageThemeRegistry::class;
    }
}
