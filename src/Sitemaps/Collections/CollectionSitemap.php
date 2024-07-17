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
            ->map(fn ($entry) => (new EntrySitemapUrl($entry, $this))->toArray())
            ->filter()
            ->values();
    }

    protected function entries(): Collection
    {
        return $this->model->queryEntries()
            ->where($this->includeInSitemapQuery(...))
            ->get()
            ->filter(IncludeInSitemap::run(...));
    }
}
