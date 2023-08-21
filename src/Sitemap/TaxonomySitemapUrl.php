<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Models\Defaults;
use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Support\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Site;

class TaxonomySitemapUrl extends BaseSitemapUrl
{
    public function __construct(protected Taxonomy $taxonomy, protected string $site, protected TaxonomySitemap $sitemap)
    {
        // We need to set the site so that we can get to correct URL of the taxonomy.
        $this->previousSite = Site::current()->handle();
        Site::setCurrent($site);
    }

    public function __destruct()
    {
        Site::setCurrent($this->previousSite);
    }

    public function loc(): string
    {
        return $this->taxonomy->absoluteUrl();
    }

    public function alternates(): ?array
    {
        $taxonomies = $this->taxonomies();

        // We only want alternate URLs if there are at least two terms.
        if ($taxonomies->count() <= 1) {
            return null;
        }

        return $taxonomies->map(function ($taxonomy, $site) {
            // We need to set the site so that we can get to correct URL of the taxonomy.
            Site::setCurrent($site);

            return [
                'hreflang' => Helpers::parseLocale(Site::current()->locale()),
                'href' => $taxonomy->absoluteUrl(),
            ];
        })->toArray();
    }

    public function lastmod(): string
    {
        if ($terms = $this->lastModifiedTaxonomyTerm()) {
            return $terms->lastModified()->format('Y-m-d\TH:i:sP');
        }

        return now()->format('Y-m-d\TH:i:sP');
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
        return $this->site;
    }

    protected function lastModifiedTaxonomyTerm(): ?Term
    {
        return $this->taxonomy->queryTerms()
            ->where('site', $this->site)
            ->get()
            ->sortByDesc(fn ($term) => $term->lastModified())
            ->first();
    }

    protected function taxonomies(): Collection
    {
        return $this->sitemap->taxonomies();
    }
}
