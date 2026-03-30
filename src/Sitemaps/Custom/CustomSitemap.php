<?php

namespace Aerni\AdvancedSeo\Sitemaps\Custom;

use Aerni\AdvancedSeo\Sitemaps\BaseSitemap;
use Statamic\Facades\Site;

abstract class CustomSitemap extends BaseSitemap
{
    protected string $handle;

    protected string $site;

    final public function type(): string
    {
        return 'custom';
    }

    final public function handle(): string
    {
        return $this->handle;
    }

    public function site(): string
    {
        return isset($this->site) ? $this->site : Site::default()->handle();
    }

    public function makeUrl(string $url): CustomSitemapUrl
    {
        return new CustomSitemapUrl($this, $url);
    }

    public static function register(): void
    {
        app('advanced-seo.sitemaps')->push(static::class);
    }
}
