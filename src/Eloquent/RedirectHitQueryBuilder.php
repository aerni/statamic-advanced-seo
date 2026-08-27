<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\RedirectHit;
use Aerni\AdvancedSeo\Contracts\RedirectHitQueryBuilder as Contract;

class RedirectHitQueryBuilder extends QueryBuilder implements Contract
{
    protected function toItem($model)
    {
        return app(RedirectHit::class)::fromModel($model);
    }
}
