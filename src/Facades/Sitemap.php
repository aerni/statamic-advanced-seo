<?php

namespace Aerni\AdvancedSeo\Facades;

use Aerni\AdvancedSeo\Sitemaps\SitemapRepository;
use Illuminate\Support\Facades\Facade;

class Sitemap extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SitemapRepository::class;
    }
}
