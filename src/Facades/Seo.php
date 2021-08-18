<?php

namespace Aerni\AdvancedSeo\Facades;

use Illuminate\Support\Facades\Facade;

class Seo extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Aerni\AdvancedSeo\Contracts\SeoRepository::class;
    }
}
