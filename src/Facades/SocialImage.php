<?php

namespace Aerni\AdvancedSeo\Facades;

use Aerni\AdvancedSeo\Registries\SocialImageRegistry;
use Illuminate\Support\Facades\Facade;

class SocialImage extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SocialImageRegistry::class;
    }
}
