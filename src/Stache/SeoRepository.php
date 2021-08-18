<?php

namespace Aerni\AdvancedSeo\Stache;

use Statamic\Stache\Stache;
use Aerni\AdvancedSeo\Data\Seo;
use Statamic\Data\DataCollection;
use Statamic\Stache\Stores\Store;
use Aerni\AdvancedSeo\Stache\SeoQueryBuilder;
use Aerni\AdvancedSeo\Contracts\SeoRepository as Contract;

class SeoRepository implements Contract
{
    protected Stache $stache;
    protected Store $store;

    public function __construct(Stache $stache)
    {
        $this->stache = $stache;
        $this->store = $stache->store('seo');
    }

    public function make()
    {
        return app(Seo::class);
    }

    public function all(): DataCollection
    {
        return $this->query()->get();
    }

    public function find($id): ?Seo
    {
        return $this->query()->where('id', $id)->first();
    }

    public function save($seo)
    {
        if (! $seo->id()) {
            $seo->id($this->stache->generateId());
        }

        $this->store->save($seo);
    }

    public function delete($seo): void
    {
        $this->store->delete($seo);
    }

    public function query(): SeoQueryBuilder
    {
        return new SeoQueryBuilder($this->store);
    }
}
