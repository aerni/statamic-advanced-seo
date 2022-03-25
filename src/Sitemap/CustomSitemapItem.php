<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Statamic\Support\Traits\FluentlyGetsAndSets;

class CustomSitemapItem extends BaseSitemapItem
{
    use FluentlyGetsAndSets;

    public function __construct(
        protected string $loc,
        protected ?string $lastmod = null,
        protected ?string $changefreq = null,
        protected ?string $priority = null,
    ) {
    }

    public function loc(string $loc = null): string|self
    {
        return $this->fluentlyGetOrSet('loc')->args(func_get_args());
    }

    public function lastmod(string $lastmod = null): string|self|null
    {
        return $this->fluentlyGetOrSet('lastmod')->args(func_get_args());
    }

    public function changefreq(string $changefreq = null): string|self|null
    {
        return $this->fluentlyGetOrSet('changefreq')->args(func_get_args());
    }

    public function priority(string $priority = null): string|self|null
    {
        return $this->fluentlyGetOrSet('priority')->args(func_get_args());
    }
}
