<?php

namespace Aerni\AdvancedSeo\Facades;

use Illuminate\Support\Facades\Facade;

class Sitemap extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Aerni\AdvancedSeo\Sitemaps\SitemapRepository::class;
    }
}
