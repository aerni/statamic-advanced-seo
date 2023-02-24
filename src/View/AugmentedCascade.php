<?php

namespace Aerni\AdvancedSeo\View;

use Aerni\AdvancedSeo\Fieldtypes\ComputedValueFieldtype;
use Statamic\Data\AbstractAugmented;
use Statamic\Fields\Value;

class AugmentedCascade extends AbstractAugmented
{
    public function keys(): array
    {
        $dataKeys = $this->data->data()->keys();

        $computedKeys = method_exists($this->data, 'computedKeys') ? $this->data->computedKeys() : null;

        return collect()
            ->merge($dataKeys)
            ->merge($computedKeys)
            ->unique()->sort()->values()->all();
    }

    protected function wrapValue($value, $handle)
    {
        $fields = $this->blueprintFields();

        /**
         * Add a dummy fieldtype for any field that doesn't have a fieldtype.
         * This is needed so that we can parse Antlers in the view.
         * Antlers can only be parsed if the Value object has a fieldtype.
         */
        $fieldtype = $fields->get($handle)?->fieldtype()
            ?? new ComputedValueFieldtype();

        return new Value($value, $handle, $fieldtype, $this->data);
    }
}
