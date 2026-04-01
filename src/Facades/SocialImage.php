<?php

namespace Aerni\AdvancedSeo\Facades;

use Aerni\AdvancedSeo\SocialImages\SocialImageRepository;
use Illuminate\Support\Facades\Facade;

class SocialImage extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SocialImageRepository::class;
    }
}
