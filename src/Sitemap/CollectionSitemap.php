<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Sitemap\CollectionSitemapItem;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Contracts\Entries\Collection as StatamicCollection;

class CollectionSitemap extends BaseSitemap
{
    protected string $type = 'collections';
    protected StatamicCollection $collection;

    public function __construct(protected string $handle, protected string $site)
    {
        $this->collection = CollectionFacade::find($handle);
    }

    public function items(): Collection
    {
        return $this->entries()
            ->map(fn ($entry) => (new CollectionSitemapItem($entry))->toArray());
    }

    protected function entries(): Collection
    {
        return $this->collection->queryEntries()
            ->where('site', $this->site)
            ->where('published', '!=', false) // We only want published entries.
            ->where('uri', '!=', null) // We only want entries that have a route. This works for both single and per-site collection routes.
            ->where('seo_noindex', '!=', true) // We only want indexable entries.
            ->get();
    }
}
