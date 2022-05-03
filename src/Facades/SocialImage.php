<?php

namespace Aerni\AdvancedSeo\Facades;

use Illuminate\Support\Facades\Facade;

class SocialImage extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Aerni\AdvancedSeo\SocialImages\SocialImageRepository::class;
    }
}
