<?php

namespace Aerni\AdvancedSeo\Actions;

use Statamic\Fields\Value;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;

class GetAugmentedDefaults
{
    public static function handle(string $type, string $handle, string $locale): Collection
    {
        return Seo::findOrMake($type, $handle)
            ->ensureLocalizations(collect($locale))
            ->in($locale)
            ->toAugmentedCollection()
            ->filter(fn ($item) => $item instanceof Value && $item->raw() !== null);
    }
}
