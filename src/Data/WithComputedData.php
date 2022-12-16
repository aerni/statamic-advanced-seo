<?php

namespace Aerni\AdvancedSeo\Data;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait WithComputedData
{
    abstract public function computedValueKeys(): Collection;

    public function computedValues(): Collection
    {
        return $this->computedValueKeys()
            ->mapWithKeys(fn ($key) => [$key => $this->computedValue($key)]);
    }

    public function computedValue(string $key): mixed
    {
        return $this->hasComputedValue($key)
            ? $this->{Str::camel($key)}()
            : null;
    }

    public function hasComputedValue(string $key): bool
    {
        return $this->computedValueKeys()->flip()->has($key)
            && method_exists($this, Str::camel($key));
    }
}
