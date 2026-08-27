<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\RedirectError;
use Aerni\AdvancedSeo\Contracts\RedirectErrorQueryBuilder as Contract;

class RedirectErrorQueryBuilder extends QueryBuilder implements Contract
{
    protected function toItem($model)
    {
        return app(RedirectError::class)::fromModel($model);
    }
}
