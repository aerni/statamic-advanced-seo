<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\RedirectErrorQueryBuilder as Contract;

class RedirectErrorQueryBuilder extends QueryBuilder implements Contract
{
    protected function toItem($model)
    {
        return RedirectError::fromModel($model);
    }
}
