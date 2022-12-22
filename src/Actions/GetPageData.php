<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Tags\Context;

class GetPageData
{
    public static function handle(mixed $model): ?Collection
    {
        $fields = OnPageSeoBlueprint::make()->items();

        $values = match (true) {
            ($model instanceof Context) => $model->intersectByKeys($fields),
            ($model instanceof Entry) => $model->toAugmentedCollection(array_keys($fields)),
            ($model instanceof Term) => $model->toAugmentedCollection(array_keys($fields)),
            default => null,
        };

        // TODO: Can we cast booleans so we always have values for `seo_generate_social_image`, `sitemap_enabled`, and such?

        /**
         * Remove any field that doesn't know how to be augmented.
         * This ensures that we don't return any values of fields that are not part of the blueprint.
         * This would typically happen when a feature like the social images generator has been disabled.
         */
        return $values?->filter(fn ($value) => $value->fieldtype());
    }
}
