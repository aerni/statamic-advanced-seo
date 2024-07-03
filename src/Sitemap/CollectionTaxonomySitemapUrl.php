<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Models\Defaults;
use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Support\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Site;
use Statamic\Facades\URL;

class CollectionTaxonomySitemapUrl extends BaseSitemapUrl
{
    public function __construct(protected Taxonomy $taxonomy, protected string $site, protected TaxonomySitemap $sitemap) {}

    public function loc(): string
    {
        return $this->getUrl($this->taxonomy, $this->site);
    }

    public function alternates(): ?array
    {
        $taxonomies = $this->taxonomies();

        // We only want alternate URLs if there are at least two terms.
        if ($taxonomies->count() <= 1) {
            return null;
        }

        return $taxonomies->map(function ($taxonomy, $site) {
            return [
                'hreflang' => Helpers::parseLocale(Site::get($site)->locale()),
                'href' => $this->getUrl($taxonomy, $site),
            ];
        })
        ->put('x-default', [
            'hreflang' => 'x-default',
            'href' => $this->getUrl($this->taxonomy, $this->taxonomy->sites()->first()),
        ])
        ->toArray();
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
        return $this->sitemap->collectionTaxonomies()
            ->filter(function ($item) {
                return $item['taxonomy']->handle() === $this->taxonomy->handle()
                    && $item['taxonomy']->collection()->handle() === $this->taxonomy->collection()->handle();
            })
            ->mapwithKeys(fn ($item) => [$item['site'] => $item['taxonomy']]);
    }

    protected function getUrl(Taxonomy $taxonomy, string $site): string
    {
        $siteUrl = $this->absoluteUrl(Site::get($site));
        $taxonomyHandle = $taxonomy->handle();
        $collectionHandle = $taxonomy->collection()->handle();

        return URL::tidy("{$siteUrl}/{$collectionHandle}/{$taxonomyHandle}");
    }
}
