<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\RedirectQueryBuilder as Contract;
use Illuminate\Support\Collection;
use Statamic\Query\EloquentQueryBuilder;

class RedirectQueryBuilder extends EloquentQueryBuilder implements Contract
{
    protected function transform($items, $columns = ['*'])
    {
        return Collection::make($items)
            ->map(fn ($model) => Redirect::fromModel($model));
    }
}
