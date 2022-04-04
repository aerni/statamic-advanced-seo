<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Models\Defaults;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class CustomSitemapUrl extends BaseSitemapUrl
{
    use FluentlyGetsAndSets;

    public function __construct(protected string $loc)
    {
    }

    public function loc(string $loc = null): string|self
    {
        return $this->fluentlyGetOrSet('loc')->args(func_get_args());
    }

    public function alternates(array $alternates = null): array|self|null
    {
        return $this->fluentlyGetOrSet('alternates')
            ->setter(function ($alternates) {
                foreach ($alternates as $alternate) {
                    throw_unless(array_key_exists('href', $alternate), new \Exception("One of your alternate links is missing the 'href' attribute."));
                    throw_unless(array_key_exists('hreflang', $alternate), new \Exception("One of your alternate links is missing the 'hreflang' attribute."));
                }

                return $alternates;
            })
            ->args(func_get_args());
    }

    public function lastmod(string $lastmod = null): string|self|null
    {
        return $this->fluentlyGetOrSet('lastmod')
            ->getter(function () {
                return $this->lastmod ?? now()->format('Y-m-d\TH:i:sP');
            })
            ->args(func_get_args());
    }

    public function changefreq(string $changefreq = null): string|self|null
    {
        return $this->fluentlyGetOrSet('changefreq')
            ->getter(function () {
                return $this->changefreq ?? Defaults::data('collections')->get('seo_sitemap_change_frequency');
            })
            ->args(func_get_args());
    }

    public function priority(string $priority = null): string|self|null
    {
        return $this->fluentlyGetOrSet('priority')
            ->getter(function () {
                return $this->priority ?? Defaults::data('collections')->get('seo_sitemap_priority');
            })
            ->args(func_get_args());
    }
}
