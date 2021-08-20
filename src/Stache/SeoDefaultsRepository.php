<?php

namespace Aerni\AdvancedSeo\Stache;

use Statamic\Stache\Stache;
use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Statamic\Data\DataCollection;
use Statamic\Stache\Stores\Store;
use Aerni\AdvancedSeo\Stache\SeoQueryBuilder;
use Aerni\AdvancedSeo\Contracts\SeoDefaultsRepository as Contract;

class SeoDefaultsRepository implements Contract
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

    public function all(): DataCollection
    {
        return $this->query()->get();
    }

    public function find(string $type, string $id): ?SeoDefaultSet
    {
        return $this->store->getItem("$type::$id");
    }

    public function save(SeoDefaultSet $set): self
    {
        $this->store->save($set);

        return $this;
    }

    public function delete(SeoDefaultSet $set): bool
    {
        $this->store->delete($set);

        return true;
    }

    public function query(): SeoQueryBuilder
    {
        return new SeoQueryBuilder($this->store);
    }
}
