<?php

namespace Aerni\AdvancedSeo\Data;

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
}
