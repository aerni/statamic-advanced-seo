<?php

namespace Aerni\AdvancedSeo\View;

use Statamic\Data\AbstractAugmented;

class AugmentedCascade extends AbstractAugmented
{
    public function keys(): array
    {
        $dataKeys = $this->data->data()->keys();

        $computedValueKeys = method_exists($this->data, 'computedValueKeys') ? $this->data->computedValueKeys() : null;

        return collect()
            ->merge($dataKeys)
            ->merge($computedValueKeys)
            ->unique()->sort()->values()->all();
    }
}
