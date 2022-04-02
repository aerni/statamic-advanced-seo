<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Support\Collection;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Site;

class TermSitemapUrl extends BaseSitemapUrl
{
    public function __construct(protected Term $term, protected TaxonomySitemap $sitemap)
    {
    }

    public function loc(): string
    {
        $url = match ($this->term->seo_canonical_type->value()) {
            'current' => $this->term->absoluteUrl(),
            'other' => $this->term->seo_canonical_entry?->absoluteUrl(),
            'custom' => $this->term->seo_canonical_custom,
            default => null,
        };

        return $url ?? $this->term->absoluteUrl();
    }

    public function alternates(): array
    {
        // If there is only one term, we don't want to render the alternate urls.
        if ($this->terms()->count() === 1) {
            return [];
        }

        return $this->terms()->map(fn ($term) => [
            'hreflang' => Helpers::parseLocale(Site::get($term->locale())->locale()),
            'href' => $term->absoluteUrl(),
        ])->toArray();
    }

    public function lastmod(): string
    {
        return $this->term->lastModified()->format('Y-m-d\TH:i:sP');
    }

    public function changefreq(): string
    {
        return $this->term->seo_sitemap_change_frequency;
    }

    public function priority(): string
    {
        // Make sure we actually return `0.0` and `1.0`.
        return number_format($this->term->seo_sitemap_priority->value(), 1);
    }

    protected function terms(): Collection
    {
        return $this->sitemap->terms($this->term->taxonomy())
            ->filter(fn ($term) => $term->slug() === $this->term->slug());
    }
}
