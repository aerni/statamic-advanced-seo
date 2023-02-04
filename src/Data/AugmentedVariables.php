<?php

namespace Aerni\AdvancedSeo\Data;

use Statamic\Data\AbstractAugmented;

class AugmentedVariables extends AbstractAugmented
{
    public function keys()
    {
        /**
         * We only want to augment fields that exist in the blueprint. This excludes any fields of disabled features.
         * This only works when getting all augmented fields though, like `toAugmentedCollection`.
         * You can still get fields of disabled features when selecting the fields to be augmented like `toAugmentedCollection('seo_sitemap_enabled')`.
         * This is due to how the `get` method in the `AbstractAugmented` class works.
         */
        $fieldsWithValues = $this->data->values()->keys()->all();

        return array_intersect($this->data->blueprintFields(), $fieldsWithValues);
    }
}
