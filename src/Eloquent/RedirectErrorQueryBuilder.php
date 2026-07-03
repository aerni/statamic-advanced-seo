<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\RedirectErrorQueryBuilder as Contract;
use Illuminate\Support\Collection;
use Statamic\Query\EloquentQueryBuilder;

class RedirectErrorQueryBuilder extends EloquentQueryBuilder implements Contract
{
    protected function transform($items, $columns = ['*'])
    {
        return Collection::make($items)
            ->map(fn ($model) => RedirectError::fromModel($model));
    }
}
