<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Illuminate\Support\Collection;
use Statamic\Facades\Collection as CollectionFacade;

class CollectionSitemap extends BaseSitemap
{
    protected string $type = 'collections';

    public function __construct(protected string $handle, protected string $site)
    {
    }

    public function items(): Collection
    {
        return $this->entries()
            ->map(fn ($entry) => (new CollectionSitemapItem($entry))->toArray());
    }

    protected function entries(): Collection
    {
        return CollectionFacade::find($this->handle)
            ->queryEntries()
            ->where('site', $this->site)
            ->where('published', '!=', false) // We only want published entries.
            ->where('uri', '!=', null) // We only want entries that have a route. This works for both single and per-site collection routes.
            ->where('seo_noindex', '!=', true) // We only want indexable entries.
            ->get();
    }
}
