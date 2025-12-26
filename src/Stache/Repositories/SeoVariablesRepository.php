<?php

namespace Aerni\AdvancedSeo\Stache\Repositories;

use Statamic\Stache\Stache;
use Statamic\Stache\Stores\Store;
use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Contracts\SeoVariablesRepository as Contract;

class SeoVariablesRepository implements Contract
{
    protected Store $store;

    public function __construct(protected Stache $stache)
    {
        $this->store = $stache->store('seo-variables');
    }

    public function all(): Collection
    {
        $keys = $this->store->paths()->keys();

        return collect($this->store->getItems($keys));
    }

    public function find(string $id): ?SeoVariables
    {
        return $this->store->getItem($id);
    }

    public function whereSet(string $type, string $handle): Collection
    {
        $typeKeys = $this->store
            ->index('type')
            ->items()
            ->filter(fn ($value) => $value == $type)
            ->keys();

        $handleKeys = $this->store
            ->index('handle')
            ->items()
            ->filter(fn ($value) => $value == $handle)
            ->keys();

        // Intersect to get variables that match both type AND handle
        $keys = $handleKeys->intersect($typeKeys);

        return $this->store->getItems($keys)->values();
    }

    public function save(SeoVariables $variables): void
    {
        $this->store->save($variables);
    }

    public function delete(SeoVariables $variables): void
    {
        $this->store->delete($variables);
    }

    public static function bindings(): array
    {
        return [
            \Aerni\AdvancedSeo\Contracts\SeoVariables::class => \Aerni\AdvancedSeo\Data\SeoVariables::class,
        ];
    }
}
