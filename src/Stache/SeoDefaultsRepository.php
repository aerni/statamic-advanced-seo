<?php

namespace Aerni\AdvancedSeo\Stache;

use Statamic\Stache\Stache;
use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Statamic\Data\DataCollection;
use Statamic\Stache\Stores\Store;
use Aerni\AdvancedSeo\Stache\SeoQueryBuilder;
use Aerni\AdvancedSeo\Contracts\SeoDefaultsRepository as Contract;

class SeoDefaultsRepository
{
    protected Stache $stache;
    protected Store $store;

    public function __construct(Stache $stache)
    {
        $this->stache = $stache;
        $this->store = $stache->store('seo');
    }

    public function make(): SeoDefaultSet
    {
        return app(SeoDefaultSet::class);
    }

    public function allOfType(string $type): DataCollection
    {
        $keys = $this->store->store($type)->paths()->keys();

        return DataCollection::make($this->store->store($type)->getItems($keys));
    }

    public function find(string $type, string $id): ?SeoDefaultSet
    {
        return $this->store->store($type)->getItem($id);
    }

    public function save(SeoDefaultSet $set): self
    {
        $this->store->store($set->type())->save($set);

        return $this;
    }

    public function delete(SeoDefaultSet $set): bool
    {
        $this->store->store($set->type())->delete($set);

        return true;
    }

    public function query(): SeoQueryBuilder
    {
        return new SeoQueryBuilder($this->store);
    }
}
