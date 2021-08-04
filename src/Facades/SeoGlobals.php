<?php

namespace Aerni\AdvancedSeo\Facades;

use Illuminate\Support\Facades\Facade;

class SeoGlobals extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Aerni\AdvancedSeo\Globals\Seo::class;
    }
}
