<?php

namespace Aerni\AdvancedSeo\Sitemaps\Collections;

use Aerni\AdvancedSeo\Actions\IncludeInSitemap;
use Aerni\AdvancedSeo\Sitemaps\BaseSitemap;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Collection as EntriesCollection;
use Statamic\Facades\Blink;

class CollectionSitemap extends BaseSitemap
{
    public function __construct(protected EntriesCollection $model) {}

    public function type(): string
    {
        return 'collection';
    }

    public function handle(): string
    {
        return $this->model->handle();
    }

    /**
     * Get the lastmod using a targeted query instead of loading all URLs.
     *
     * The parent implementation loads all entries via urls() just to find the max lastmod.
     * This override uses a single query with orderByDesc, avoiding the expensive hydration
     * of all entries, IncludeInSitemap filtering, and URL object creation.
     *
     * Tradeoff: skips post-query IncludeInSitemap checks (noindex, sitemap_enabled, etc.),
     * so the lastmod may come from an excluded entry. This is acceptable because lastmod
     * on a sitemap index is a crawl hint — search engines don't validate it against contents.
     */
    public function lastmod(): ?string
    {
        return $this->model->queryEntries()
            ->where($this->includeInSitemapQuery(...))
            ->orderByDesc('last_modified')
            ->first()
            ?->lastModified()
            ?->format('Y-m-d\TH:i:sP');
    }

    public function urls(): Collection
    {
        return Blink::once($this->cacheKey(), function () {
            return $this->entries()
                ->mapInto(EntrySitemapUrl::class)
                ->each->sitemap($this);
        });
    }

    protected function entries(): Collection
    {
        return $this->model->queryEntries()
            ->where($this->includeInSitemapQuery(...))
            ->get()
            ->filter(IncludeInSitemap::run(...));
    }
}
