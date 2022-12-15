<?php

namespace Aerni\AdvancedSeo\View;

use Statamic\Data\AbstractAugmented;

class AugmentedCascade extends AbstractAugmented
{
    public function keys(): array
    {
        return collect()
            ->merge($this->data->data()->keys())
            ->merge($this->data->computedValueKeys())
            ->unique()->sort()->values()->all();
    }
}
