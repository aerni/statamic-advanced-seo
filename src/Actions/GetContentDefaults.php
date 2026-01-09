<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Context\Context;
use Illuminate\Support\Collection;
use Statamic\Facades\Blink;

class GetContentDefaults
{
    public static function handle(mixed $model): Collection
    {
        if (! $context = Context::from($model)) {
            return collect();
        }

        return Blink::once(
            "advanced-seo::{$context->type}::{$context->handle}::{$context->site}",
            fn () => GetAugmentedDefaults::handle($context)
        );
    }
}
