<?php

namespace Aerni\AdvancedSeo\View;

use Statamic\Data\AbstractAugmented;

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
}
