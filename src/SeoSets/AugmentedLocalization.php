<?php

namespace Aerni\AdvancedSeo\SeoSets;

use Statamic\Data\AbstractAugmented;

class AugmentedLocalization extends AbstractAugmented
{
    public function keys()
    {
        /**
         * Return all blueprint fields, which excludes any fields of disabled features.
         * Fields without values will return null or their augmented method result if defined.
         */
        return $this->data->blueprintFields();
    }

    /**
     * Augment site_name with a fallback to the Statamic site name.
     */
    public function siteName(): string
    {
        return $this->data->value('site_name') ?? $this->data->site()->name();
    }
}
