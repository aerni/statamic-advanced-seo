<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Models\Defaults;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Aerni\AdvancedSeo\Sitemap\BaseSitemapItem;

class TaxonomySitemapItem extends BaseSitemapItem
{
    public function __construct(protected Taxonomy|Term $model, protected string $site)
    {
    }

    public function loc(): string
    {
        if ($this->model instanceof Taxonomy) {
            return $this->model->absoluteUrl();
        }

        $url = match ($this->model->seo_canonical_type->value()) {
            'current' => $this->model->absoluteUrl(),
            'other' => $this->model->seo_canonical_entry?->absoluteUrl(),
            'custom' => $this->model->seo_canonical_custom,
            default => null,
        };

        return $url ?? $this->model->absoluteUrl();
    }

    public function lastmod(): string
    {
        if ($this->model instanceof Taxonomy) {
            return $this->lastModifiedTaxonomyTerm()->lastModified()->format('Y-m-d\TH:i:sP');
        }

        return $this->model->lastModified()->format('Y-m-d\TH:i:sP');
    }

    protected function lastModifiedTaxonomyTerm(): Term
    {
        return $this->model->queryTerms()
            ->where('site', $this->site)
            ->get()
            ->sortByDesc(fn ($term) => $term->lastModified())
            ->first();
    }

    public function changefreq(): string
    {
        if ($this->model instanceof Taxonomy) {
            return Defaults::data('taxonomies')->get('seo_sitemap_change_frequency');
        }

        return $this->model->seo_sitemap_change_frequency;
    }

    public function priority(): string
    {
        if ($this->model instanceof Taxonomy) {
            return Defaults::data('taxonomies')->get('seo_sitemap_priority');
        }

        return $this->model->seo_sitemap_priority;
    }
}
