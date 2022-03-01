<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Collection as StatamicCollection;
use Statamic\Facades\Collection as CollectionFacade;

class CollectionSitemap extends BaseSitemap
{
    protected StatamicCollection $collection;

    public function __construct(protected string $handle, protected string $site)
    {
        $this->collection = CollectionFacade::find($handle);
    }

    public function type(): string
    {
        return 'collections';
    }

    public function items(): Collection
    {
        return $this->entries()
            ->map(fn ($item) => (new SitemapItem($item, $this->site))->toArray());
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
