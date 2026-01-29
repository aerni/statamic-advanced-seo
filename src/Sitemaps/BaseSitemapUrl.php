<?php

namespace Aerni\AdvancedSeo\Sitemaps;

use Aerni\AdvancedSeo\Contracts\Sitemap;
use Aerni\AdvancedSeo\Contracts\SitemapUrl;
use Illuminate\Contracts\Support\Arrayable;
use Statamic\Support\Traits\FluentlyGetsAndSets;

abstract class BaseSitemapUrl implements Arrayable, SitemapUrl
{
    use FluentlyGetsAndSets;

    protected ?Sitemap $sitemap = null;

    abstract public function loc(): string|self;

    abstract public function alternates(): array|self|null;

    abstract public function lastmod(): string|self|null;

    abstract public function changefreq(): string|self|null;

    abstract public function priority(): string|self|null;

    abstract public function site(): string|self;

    public function sitemap(?Sitemap $sitemap = null): self|Sitemap|null
    {
        return $this->fluentlyGetOrSet('sitemap')->args(func_get_args());
    }

    public function toArray(): array
    {
        return [
            'loc' => $this->loc(),
            'alternates' => $this->alternates(),
            'lastmod' => $this->lastmod(),
            'changefreq' => $this->changefreq(),
            'priority' => $this->priority(),
            'site' => $this->site(),
        ];
    }
}
