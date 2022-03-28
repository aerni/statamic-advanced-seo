<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Collection as EntriesCollection;

class CollectionSitemap extends BaseSitemap
{
    public function __construct(protected EntriesCollection $model)
    {
    }

    public function urls(): Collection
    {
        return $this->entries()
            ->map(fn ($entry) => (new CollectionSitemapItem($entry, $this))->toArray());
    }

    protected function entries(): Collection
    {
        return $this->model
            ->queryEntries()
            ->where('published', '!=', false) // We only want published entries.
            ->where('uri', '!=', null) // We only want entries that have a route. This works for both single and per-site collection routes.
            ->where('seo_noindex', '!=', true) // We only want indexable entries.
            ->get()
            ->filter(fn ($entry) => $this->indexable($entry)); // Filter out any entries that are not indexable.
    }
}
