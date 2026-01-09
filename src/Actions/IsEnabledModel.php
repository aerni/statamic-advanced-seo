<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Context\Context;

class IsEnabledModel
{
    public static function handle(mixed $model): bool
    {
        return Context::from($model)?->seoSet()?->enabled() ?? false;
    }
}
