<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\RedirectQueryBuilder as Contract;

class RedirectQueryBuilder extends QueryBuilder implements Contract
{
    protected function toItem($model)
    {
        return Redirect::fromModel($model);
    }
}
