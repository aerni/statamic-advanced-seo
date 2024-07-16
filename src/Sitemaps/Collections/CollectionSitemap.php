<?php

namespace Aerni\AdvancedSeo\Sitemaps\Collections;

use Aerni\AdvancedSeo\Actions\IncludeInSitemap;
use Aerni\AdvancedSeo\Sitemaps\BaseSitemap;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Collection as EntriesCollection;

class CollectionSitemap extends BaseSitemap
{
    public function __construct(protected EntriesCollection $model) {}

    public function urls(): Collection
    {
        return $this->entries()
            ->map(fn ($entry) => (new CollectionSitemapUrl($entry, $this))->toArray())
            ->filter();
    }

    protected function entries(): Collection
    {
        return $this->model
            ->queryEntries()
            ->where('published', true) // We only want published entries.
            ->whereNotNull('url') // We only want entries that have a route.
            ->where('seo_noindex', false) // We only want indexable entries.
            ->where('seo_sitemap_enabled', true) // We only want entries that are enabled for the sitemap.
            ->where('seo_canonical_type', 'current') // Only include entries with the current URL as the canonical URL.
            ->get()
            ->filter(IncludeInSitemap::run(...)); // We only want indexable entries.
    }
}
