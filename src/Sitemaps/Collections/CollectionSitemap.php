<?php

namespace Aerni\AdvancedSeo\Sitemaps\Collections;

use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Actions\Indexable;
use Aerni\AdvancedSeo\Sitemaps\BaseSitemap;
use Statamic\Contracts\Entries\Collection as EntriesCollection;

class CollectionSitemap extends BaseSitemap
{
    public function __construct(protected EntriesCollection $model)
    {
    }

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
            ->where('published', '!=', false) // We only want published entries.
            ->where('uri', '!=', null) // We only want entries that have a route. This works for both single and per-site collection routes.
            ->get()
            ->filter(fn ($entry) => Indexable::handle($entry)); // We only want indexable entries.
    }
}
