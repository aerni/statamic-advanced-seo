<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Actions\Indexable;
use Aerni\AdvancedSeo\Actions\SupplementDefaultsData;
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
            ->map(fn ($entry) => (new CollectionSitemapUrl($entry))->toArray())
            ->filter();
    }

    protected function entries(): Collection
    {
        return $this->model
            ->queryEntries()
            ->where('published', '!=', false) // We only want published entries.
            ->where('uri', '!=', null) // We only want entries that have a route. This works for both single and per-site collection routes.
            ->get()
            // ->each(fn ($entry) => $entry->cacheBlueprint(false)) // TODO: This is dependant on an open PR: https://github.com/statamic/cms/pull/5702
            // ->map(fn ($entry) => SupplementDefaultsData::handle($entry)) // Make sure to get the correct localization when extending the blueprint
            ->filter(fn ($entry) => Indexable::handle($entry)); // We only want indexable entries.
    }
}
