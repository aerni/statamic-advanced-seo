<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Statamic\Facades\Site;
use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Support\Helpers;
use Statamic\Contracts\Taxonomies\Term;
use Aerni\AdvancedSeo\Sitemap\TaxonomySitemap;

class TermSitemapItem extends BaseSitemapItem
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
        return $this->term->seo_sitemap_priority;
    }

    protected function terms(): Collection
    {
        return $this->term->term()->localizations();
    }
}
