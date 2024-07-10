<?php

namespace Aerni\AdvancedSeo\View;

use Aerni\AdvancedSeo\Fieldtypes\CascadeFieldtype;
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

    protected function wrapDataMethodInvokable(string $method, string $handle)
    {
        return new Value(
            fn () => $this->data->$method(),
            $handle,
            app(CascadeFieldtype::class), // Add a dummy fieldtype to enable Value objects to parse Antlers.
            $this->data
        );
    }

    protected function wrapDeferredValue($handle)
    {
        return new Value(
            fn () => $this->getFromData($handle),
            $handle,
            app(CascadeFieldtype::class), // Add a dummy fieldtype to enable Value objects to parse Antlers.
            $this->data
        );
    }
}
