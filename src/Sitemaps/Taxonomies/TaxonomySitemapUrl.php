<?php

namespace Aerni\AdvancedSeo\Sitemaps\Taxonomies;

use Aerni\AdvancedSeo\Models\Defaults;
use Aerni\AdvancedSeo\Sitemaps\BaseSitemapUrl;
use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Support\Facades\Cache;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Site;

class TaxonomySitemapUrl extends BaseSitemapUrl
{
    public function __construct(protected Taxonomy $taxonomy, protected string $site, protected TaxonomySitemap $sitemap)
    {
    }

    public function loc(): string
    {
        Site::setCurrent($this->site);

        return $this->absoluteUrl($this->taxonomy);
    }

    public function alternates(): ?array
    {
        if (! Site::multiEnabled()) {
            return null;
        }

        $sites = $this->sitemap->taxonomies()->keys();

        if ($sites->count() < 2) {
            return null;
        }

        $hreflang = $sites->map(function ($site) {
            // Set the site so we can get the localized absolute URLs of the taxonomy.
            Site::setCurrent($site);

            return [
                'href' => $this->absoluteUrl($this->taxonomy),
                'hreflang' => Helpers::parseLocale(Site::current()->locale()),
            ];
        });

        $originSite = $this->taxonomy->sites()->first();

        $xDefaultSite = $sites->contains($originSite) ? $originSite : $this->site;

        // Set the site so we can get the localized absolute URL for the x-default.
        Site::setCurrent($xDefaultSite);

        return $hreflang->push([
            'href' => $this->absoluteUrl($this->taxonomy),
            'hreflang' => 'x-default',
        ])->values()->all();
    }

    public function lastmod(): string
    {
        if ($term = $this->lastModifiedTaxonomyTerm()) {
            return $term->lastModified()->format('Y-m-d\TH:i:sP');
        }

        return Cache::rememberForever(
            "advanced-seo::sitemaps::taxonomy::{$this->taxonomy}::lastmod",
            fn () => now()->format('Y-m-d\TH:i:sP')
        );
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
            ->orderByDesc('last_modified')
            ->first();
    }
}
