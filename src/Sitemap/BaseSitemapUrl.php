<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Contracts\SitemapUrl;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\URL;
use Statamic\Sites\Site;

abstract class BaseSitemapUrl implements SitemapUrl
{
    abstract public function loc(): string|self;

    abstract public function alternates(): array|self|null;

    abstract public function lastmod(): string|self|null;

    abstract public function changefreq(): string|self|null;

    abstract public function priority(): string|self|null;

    abstract public function site(): string|self;

    // TODO: Can we use the canonicalPointsToAnotherUrl method instead? It's the same logic.
    public function isCanonicalUrl(): bool
    {
        return true;
    }

    /**
     * We need to cast the data to an array so that we can get the correct url of taxonomies in the view.
     * That's because we are temporarily setting the current site in \Aerni\AdvancedSeo\Sitemap\TaxonomySitemapUrl::class,
     * which won't have any effect if we are directly accessing the data methods in the view instead.
     */
    public function toArray(): ?array
    {
        if (! $this->isCanonicalUrl()) {
            return null;
        }

        return [
            'loc' => $this->loc(),
            'alternates' => $this->alternates(),
            'lastmod' => $this->lastmod(),
            'changefreq' => $this->changefreq(),
            'priority' => $this->priority(),
            'site' => $this->site(),
        ];
    }

    protected function absoluteUrl(Entry|Taxonomy|Term|Site $model): string
    {
        return $this->sitemap->baseUrl()
            ? URL::assemble($this->sitemap->baseUrl(), $model->url())
            : $model->absoluteUrl();
    }
}
