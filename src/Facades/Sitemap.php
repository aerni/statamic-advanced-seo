<?php

namespace Aerni\AdvancedSeo\Facades;

use Aerni\AdvancedSeo\Sitemaps\Custom\SitemapBuilder;
use Aerni\AdvancedSeo\Sitemaps\SitemapIndex;
use Aerni\AdvancedSeo\Sitemaps\SitemapService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Collection all()
 * @method static ?SitemapIndex index(string $site)
 * @method static SitemapBuilder make(string $handle)
 * @method static void generate(?string $site = null)
 * @method static string xsl()
 * @method static string path(string $domain = '', string $filename = '')
 *
 * @see SitemapService
 */
class Sitemap extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SitemapService::class;
    }
}
