<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;

class GetAugmentedDefaults
{
    public static function handle(Context $context): Collection
    {
        return Seo::find("{$context->type}::{$context->handle}")
            ?->in($context->site)
            ?->toAugmentedCollection()
            ?? collect();
    }
}
