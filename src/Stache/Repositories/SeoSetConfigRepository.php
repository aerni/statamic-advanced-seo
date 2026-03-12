<?php

namespace Aerni\AdvancedSeo\Stache\Repositories;

use Aerni\AdvancedSeo\Contracts\SeoSetConfig;
use Aerni\AdvancedSeo\Contracts\SeoSetConfigRepository as Contract;
use Illuminate\Support\Collection;
use Statamic\Stache\Stache;
use Statamic\Stache\Stores\Store;

class SeoSetConfigRepository implements Contract
{
    protected Store $store;

    public function __construct(protected Stache $stache)
    {
        $this->store = $stache->store('seo-set-configs');
    }

    public function make(): SeoSetConfig
    {
        return app(SeoSetConfig::class);
    }

    public function find(string $id): ?SeoSetConfig
    {
        return $this->store->getItem($id);
    }

    public function findOrMake(string $id): SeoSetConfig
    {
        return $this->find($id) ?? $this->make();
    }

    public function all(): Collection
    {
        $keys = $this->store->paths()->keys();

        return $this->store->getItems($keys);
    }

    public function save(SeoSetConfig $config): void
    {
        $this->store->save($config);
    }

    public function delete(SeoSetConfig $config): void
    {
        $this->store->delete($config);
    }

    public static function bindings(): array
    {
        return [
            SeoSetConfig::class => \Aerni\AdvancedSeo\Data\SeoSetConfig::class,
        ];
    }
}
