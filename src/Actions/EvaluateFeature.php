<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Data\DefaultsData;
use Statamic\Facades\Blink;

class EvaluateFeature
{
    public static function handle(string $feature, ?DefaultsData $data = null): bool
    {
        return $data
            ? Blink::once("advanced-seo::features::{$feature}::{$data->id()}", fn () => resolve($feature)::enabled($data))
            : Blink::once("advanced-seo::features::{$feature}", fn () => resolve($feature)::enabled());
    }
}
