<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Illuminate\Support\Collection;
use Statamic\Query\EloquentQueryBuilder;

abstract class QueryBuilder extends EloquentQueryBuilder
{
    abstract protected function toItem($model);

    protected function transform($items, $columns = ['*'])
    {
        return Collection::make($items)->map(fn ($model) => $this->toItem($model));
    }
}
