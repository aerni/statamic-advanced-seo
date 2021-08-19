<?php

namespace Aerni\AdvancedSeo\Stache;

use Statamic\Data\DataCollection;
use Statamic\Stache\Query\Builder;

class SeoQueryBuilder extends Builder
{
    protected $types;

    public function where($column, $operator = null, $value = null)
    {
        if ($column === 'type') {
            $this->types[] = $operator;

            return $this;
        }

        return parent::where($column, $operator, $value);
    }

    protected function getFilteredKeys()
    {
        $types = empty($this->types)
            ? ['collections', 'taxonomies', 'site']
            : $this->types;

        return empty($this->wheres)
            ? $this->getKeysFromTypes($types)
            : $this->getKeysFromTypesWithWheres($types, $this->wheres);
    }

    protected function getKeysFromTypes($types)
    {
        return collect($types)->flatMap(function ($type) {
            $keys = $this->store->store($type)->paths()->keys();

            return collect($keys)->map(function ($key) use ($type) {
                return "{$type}::{$key}";
            });
        });
    }

    protected function getKeysFromTypesWithWheres($types, $wheres)
    {
        return collect($wheres)->reduce(function ($ids, $where) use ($types) {
            // Get a single array comprised of the items from the same index across all types.
            $items = collect($types)->flatMap(function ($type) use ($where) {
                return $this->store->store($type)
                    ->index($where['column'])->items()
                    ->mapWithKeys(function ($item, $key) use ($type) {
                        return ["{$type}::{$key}" => $item];
                    });
            });

            // Perform the filtering, and get the keys (the references, we don't care about the values).
            $method = 'filterWhere'.$where['type'];
            $keys = $this->{$method}($items, $where)->keys();

            // Continue intersecting the keys across the where clauses.
            // If a key exists in the reduced array but not in the current iteration, it should be removed.
            // On the first iteration, there's nothing to intersect, so just use the result as a starting point.
            return $ids ? $ids->intersect($keys)->values() : $keys;
        });
    }

    protected function collect($items = [])
    {
        return new DataCollection($items);
    }

    protected function getOrderKeyValuesByIndex()
    {
        $types = empty($this->types)
            ? ['collections', 'taxonomies', 'site']
            : $this->types;

        // First, we'll get the values from each index grouped by type
        $keys = collect($types)->map(function ($type) {
            $store = $this->store->store($type);

            return collect($this->orderBys)->mapWithKeys(function ($orderBy) use ($type, $store) {
                $items = $store->index($orderBy->sort)
                    ->items()
                    ->mapWithKeys(function ($item, $key) use ($type) {
                        return ["{$type}::{$key}" => $item];
                    })->all();

                return [$orderBy->sort => $items];
            });
        });

        // Then, we'll merge all the corresponding index values together from each type.
        return $keys->reduce(function ($carry, $type) {
            foreach ($type as $sort => $values) {
                $carry[$sort] = array_merge($carry[$sort] ?? [], $values);
            }

            return $carry;
        }, collect());
    }
}
