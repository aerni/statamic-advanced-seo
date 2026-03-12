<?php

namespace Aerni\AdvancedSeo\Facades;

use Aerni\AdvancedSeo\Registries\SitemapRegistry;
use Illuminate\Support\Facades\Facade;

class Sitemap extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SitemapRegistry::class;
    }
}
