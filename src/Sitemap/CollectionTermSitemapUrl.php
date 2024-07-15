<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Statamic\Facades\Site;
use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Support\Helpers;
use Statamic\Contracts\Taxonomies\Term;
use Aerni\AdvancedSeo\Actions\Indexable;

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

        if ($terms->count() < 2) {
            return null;
        }

        $hreflang = $terms->map(fn ($term) => [
            'href' => $this->absoluteUrl($term),
            'hreflang' => Helpers::parseLocale($term->site()->locale()),
        ]);

        $origin = $this->term->origin();

        $xDefault = Indexable::handle($origin) ? $origin : $this->term;

        return $hreflang->push([
            'href' => $this->absoluteUrl($xDefault),
            'hreflang' => 'x-default',
        ])->values()->all();
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
