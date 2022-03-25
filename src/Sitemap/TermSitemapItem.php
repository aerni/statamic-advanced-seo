<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Statamic\Contracts\Taxonomies\Term;

class TermSitemapItem extends BaseSitemapItem
{
    public function __construct(protected Term $term)
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
}
