<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Aerni\AdvancedSeo\Stache\SeoDefaultsRepository as StacheRepository;
use Illuminate\Support\Collection;
use Statamic\Data\DataCollection;

class SeoDefaultsRepository extends StacheRepository
{
    public function make(): SeoDefaultSet
    {
        return app(SeoDefaultSet::class);
    }

    public function find(string $type, string $handle): ?SeoDefaultSet
    {
        return $this->store->store($type)->getItem($handle);
    }

    public function findOrMake(string $type, string $handle): SeoDefaultSet
    {
        return $this->find($type, $handle) ?? $this->make()->type($type)->handle($handle);
    }

    public function all(): Collection
    {
        return $this->store->discoverStores()->map(function ($store, $type) {
            return $this->allOfType($type);
        });
    }

    public function allOfType(string $type): DataCollection
    {
        $keys = $this->store->store($type)->paths()->keys();

        return DataCollection::make($this->store->store($type)->getItems($keys));
    }

    public function save(SeoDefaultSet $set): self
    {
        $model = $set->toModel();
        $model->save();

        $set->model($model->fresh());

        return $this;
    }

    public function delete(SeoDefaultSet $set): bool
    {
        $set->model()->delete();

        return true;
    }
}
