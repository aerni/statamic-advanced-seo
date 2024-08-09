<?php

namespace Aerni\AdvancedSeo\Sitemaps\Taxonomies;

use Aerni\AdvancedSeo\Models\Defaults;
use Aerni\AdvancedSeo\Sitemaps\BaseSitemapUrl;
use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Site;
use Statamic\Facades\URL;

class CollectionTaxonomySitemapUrl extends BaseSitemapUrl
{
    public function __construct(protected Taxonomy $taxonomy, protected string $site, protected TaxonomySitemap $sitemap) {}

    public function loc(): string
    {
        return $this->collectionTaxonomyUrl($this->taxonomy, $this->site);
    }

    public function alternates(): ?array
    {
        if (! Site::multiEnabled()) {
            return null;
        }

        $sites = $this->taxonomies()->keys();

        if ($sites->count() < 2) {
            return null;
        }

        $hreflang = $sites->map(fn ($site) => [
            'href' => $this->collectionTaxonomyUrl($this->taxonomy, $site),
            'hreflang' => Helpers::parseLocale(Site::get($site)->locale()),
        ]);

        $originSite = $this->taxonomy->sites()->first();

        $xDefaultSite = $sites->contains($originSite) ? $originSite : $this->site;

        return $hreflang->push([
            'href' => $this->collectionTaxonomyUrl($this->taxonomy, $xDefaultSite),
            'hreflang' => 'x-default',
        ])->values()->all();
    }

    public function lastmod(): string
    {
        if ($term = $this->lastModifiedTaxonomyTerm()) {
            /* Ensure we are getting a fresh last modified date in case that there is no last modified taxonomy term. */
            Cache::forget("advanced-seo::sitemaps::collection-taxonomy::{$this->taxonomy}::lastmod");

            return $term->lastModified()->format('Y-m-d\TH:i:sP');
        }

        return Cache::rememberForever(
            "advanced-seo::sitemaps::collection-taxonomy::{$this->taxonomy}::lastmod",
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

    protected function taxonomies(): Collection
    {
        return $this->sitemap->collectionTaxonomies()
            ->filter(function ($item) {
                return $item['taxonomy']->handle() === $this->taxonomy->handle()
                    && $item['taxonomy']->collection()->handle() === $this->taxonomy->collection()->handle();
            })
            ->mapwithKeys(fn ($item) => [$item['site'] => $item['taxonomy']]);
    }

    // TODO: Should be able to remove this once https://github.com/statamic/cms/pull/10439 is merged.
    protected function collectionTaxonomyUrl(Taxonomy $taxonomy, string $site): string
    {
        $siteUrl = $this->absoluteUrl(Site::get($site));
        $taxonomyHandle = $taxonomy->handle();
        $collectionHandle = $taxonomy->collection()->handle();

        return URL::tidy("{$siteUrl}/{$collectionHandle}/{$taxonomyHandle}");
    }
}
