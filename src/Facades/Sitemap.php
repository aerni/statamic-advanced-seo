<?php

namespace Aerni\AdvancedSeo\Facades;

use Aerni\AdvancedSeo\Registries\SitemapRegistry;
use Aerni\AdvancedSeo\Sitemaps\Custom\SitemapBuilder;
use Aerni\AdvancedSeo\Sitemaps\SitemapIndex;
use Illuminate\Support\Facades\Facade;

/**
 * @method static SitemapBuilder make(string $handle)
 * @method static ?SitemapIndex index(string $site)
 * @method static void generate(?string $site = null)
 * @method static string xsl()
 * @method static string path(string $domain = '', string $filename = '')
 */
class Sitemap extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SitemapRegistry::class;
    }
}
