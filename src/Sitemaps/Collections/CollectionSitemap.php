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
