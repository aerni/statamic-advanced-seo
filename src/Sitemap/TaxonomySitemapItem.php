<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Models\Defaults;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Aerni\AdvancedSeo\Sitemap\BaseSitemapItem;

class TaxonomySitemapItem extends BaseSitemapItem
{
    public function __construct(protected Taxonomy $taxonomy, protected string $site)
    {
    }

    public function loc(): string
    {
        return $this->taxonomy->absoluteUrl();
    }

    public function lastmod(): string
    {
        return $this->lastModifiedTaxonomyTerm()->lastModified()->format('Y-m-d\TH:i:sP');
    }

    public function changefreq(): string
    {
        return Defaults::data('taxonomies')->get('seo_sitemap_change_frequency');
    }

    public function priority(): string
    {
        return Defaults::data('taxonomies')->get('seo_sitemap_priority');
    }

    protected function lastModifiedTaxonomyTerm(): Term
    {
        return $this->taxonomy->queryTerms()
            ->where('site', $this->site)
            ->get()
            ->sortByDesc(fn ($term) => $term->lastModified())
            ->first();
    }
}
