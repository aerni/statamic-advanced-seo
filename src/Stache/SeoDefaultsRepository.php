<?php

namespace Aerni\AdvancedSeo\Stache;

use Statamic\Stache\Stache;
use Aerni\AdvancedSeo\Data\SeoDefault;
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

    public function make(): SeoDefault
    {
        return app(SeoDefault::class);
    }

    public function all(): DataCollection
    {
        return $this->query()->get();
    }

    public function find(string $type, string $handle): ?SeoDefault
    {
        return $this->query()
            ->where('type', $type)
            ->where('handle', $handle)
            ->first();
    }

    public function whereType(string $handle): DataCollection
    {
        return $this->query()->where('type', $handle)->get();
    }

    public function save(SeoDefault $default): self
    {
        $this->store->store($default->type())->save($default);

        return $this;
    }

    public function delete($seo): bool
    {
        $this->store->delete($seo);

        return true;
    }

    public function query(): SeoQueryBuilder
    {
        return new SeoQueryBuilder($this->store);
    }
}
