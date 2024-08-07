<?php

namespace Aerni\AdvancedSeo\Sitemaps\Taxonomies;

use Aerni\AdvancedSeo\Actions\IncludeInSitemap;
use Aerni\AdvancedSeo\Models\Defaults;
use Aerni\AdvancedSeo\Sitemaps\BaseSitemapUrl;
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

        if ($terms->count() < 2) {
            return null;
        }

        $hreflang = $terms->map(fn ($term) => [
            'href' => $this->absoluteUrl($term),
            'hreflang' => Helpers::parseLocale($term->site()->locale()),
        ]);

        $origin = $this->term->origin();

        $xDefault = IncludeInSitemap::run($origin) ? $origin : $this->term;

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
        return Defaults::data('taxonomies')->get('seo_sitemap_change_frequency');
    }

    public function priority(): string
    {
        return Defaults::data('taxonomies')->get('seo_sitemap_priority');
    }

    public function site(): string
    {
        return $this->term->locale();
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
