<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Support\Collection;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Site;

class CollectionTermSitemapUrl extends BaseSitemapUrl
{
    public function __construct(protected Term $term, protected TaxonomySitemap $sitemap) {}

    public function loc(): string
    {
        return $this->absoluteUrl($this->term);
    }

    public function alternates(): ?array
    {
        if (! Site::multiEnabled()) {
            return null;
        }

        $terms = $this->terms();

        // We only want alternate URLs if there are at least two terms.
        if ($terms->count() <= 1) {
            return null;
        }

        return $terms->map(fn ($term) => [
            'hreflang' => Helpers::parseLocale(Site::get($term->locale())->locale()),
            'href' => $this->absoluteUrl($term),
        ])
            ->put('x-default', [
                'hreflang' => 'x-default',
                'href' => $this->absoluteUrl($this->term->origin()),
            ])
            ->toArray();
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

    public function site(): string
    {
        return $this->term->site()->handle();
    }

    public function isCanonicalUrl(): bool
    {
        return match ($this->term->seo_canonical_type->value()) {
            'current' => true,
            default => false,
        };
    }

    protected function terms(): Collection
    {
        return $this->sitemap->collectionTerms()
            ->filter(function ($term) {
                return $term->id() === $this->term->id()
                    && $term->term()->collection()->handle() === $this->term->term()->collection()->handle();
            });
    }
}
