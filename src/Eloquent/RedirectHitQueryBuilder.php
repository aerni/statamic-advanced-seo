<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\RedirectHitQueryBuilder as Contract;
use Illuminate\Support\Collection;
use Statamic\Query\EloquentQueryBuilder;

class RedirectHitQueryBuilder extends EloquentQueryBuilder implements Contract
{
    protected function transform($items, $columns = ['*'])
    {
        return Collection::make($items)
            ->map(fn ($model) => RedirectHit::fromModel($model));
    }
}
