<?php

namespace Aerni\AdvancedSeo\Sitemaps\Custom;

use Aerni\AdvancedSeo\Models\Defaults;
use Aerni\AdvancedSeo\Sitemaps\BaseSitemapUrl;
use Illuminate\Support\Carbon;
use Statamic\Facades\Site;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class CustomSitemapUrl extends BaseSitemapUrl
{
    use FluentlyGetsAndSets;

    public function __construct(
        protected string $loc,
        protected ?array $alternates = null,
        protected ?string $lastmod = null,
        protected ?string $changefreq = null,
        protected ?string $priority = null,
        protected ?string $site = null,
    ) {}

    public function loc(?string $loc = null): string|self
    {
        return $this->fluentlyGetOrSet('loc')
            ->getter(fn ($loc) => $this->absoluteUrl($loc))
            ->args(func_get_args());
    }

    public function alternates(?array $alternates = null): array|self|null
    {
        return $this->fluentlyGetOrSet('alternates')
            ->setter(function ($alternates) {
                foreach ($alternates as $alternate) {
                    throw_unless(array_key_exists('href', $alternate), new \Exception("One of your alternate links is missing the 'href' attribute."));
                    throw_unless(array_key_exists('hreflang', $alternate), new \Exception("One of your alternate links is missing the 'hreflang' attribute."));
                }

                return $alternates;
            })
            ->getter(function ($alternates) {
                return collect($alternates)->map(function ($alternate) {
                    $alternate['href'] = $this->absoluteUrl($alternate['href']);
                    return $alternate;
                })->all();
            })
            ->args(func_get_args());
    }

    public function lastmod(?Carbon $lastmod = null): string|self|null
    {
        return $this->fluentlyGetOrSet('lastmod')
            ->getter(fn () => $this->lastmod ?? now()->format('Y-m-d\TH:i:sP'))
            ->setter(fn ($lastmod) => $lastmod->format('Y-m-d\TH:i:sP'))
            ->args(func_get_args());
    }

    public function changefreq(?string $changefreq = null): string|self|null
    {
        return $this->fluentlyGetOrSet('changefreq')
            ->getter(fn () => $this->changefreq ?? Defaults::data('collections')->get('seo_sitemap_change_frequency'))
            ->setter(function ($changefreq) {
                $allowedValues = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
                $allowedValuesString = implode(', ', $allowedValues);

                throw_unless(in_array($changefreq, $allowedValues), new \Exception("Make sure to use a valid 'changefreq' value. Valid values are: [$allowedValuesString]."));

                return $changefreq;
            })
            ->args(func_get_args());
    }

    public function priority(?string $priority = null): string|self|null
    {
        return $this->fluentlyGetOrSet('priority')
            ->getter(fn () => $this->priority ?? Defaults::data('collections')->get('seo_sitemap_priority'))
            ->setter(function ($priority) {
                $allowedValues = ['0.0', '0.1', '0.2', '0.3', '0.4', '0.5', '0.6', '0.7', '0.8', '0.9', '1.0'];
                $allowedValuesString = implode(', ', $allowedValues);

                throw_unless(in_array($priority, $allowedValues), new \Exception("Make sure to use a valid 'priority' value. Valid values are: [$allowedValuesString]."));

                return $priority;
            })
            ->args(func_get_args());
    }

    public function site(?string $site = null): string|self
    {
        return $this->fluentlyGetOrSet('site')
            ->getter(fn () => $this->site ?? Site::default()->handle())
            ->args(func_get_args());
    }
}
