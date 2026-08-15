<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\RedirectQueryBuilder as Contract;

class RedirectQueryBuilder extends QueryBuilder implements Contract
{
    protected function toItem($model)
    {
        return Redirect::fromModel($model);
    }

    public function orderBy($column, $direction = 'asc')
    {
        if (in_array($column, ['hits', 'last_hit_at'], true)) {
            $hits = app('statamic.eloquent.redirect_hit.model')->getTable();
            $redirects = $this->builder->getModel()->getTable();
            $hitColumn = $column === 'hits' ? 'count' : 'last_hit_at';
            $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

            $this->builder->orderByRaw(
                "COALESCE((SELECT {$hitColumn} FROM {$hits} WHERE {$hits}.redirect = {$redirects}.id), 0) {$direction}"
            );

            return $this;
        }

        $this->builder->orderBy($column, $direction);

        return $this;
    }
}
