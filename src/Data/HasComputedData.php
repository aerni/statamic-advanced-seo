<?php

namespace Aerni\AdvancedSeo\Data;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait HasComputedData
{
    abstract public function computedKeys(): Collection;

    public function computedValues(): Collection
    {
        return $this->computedKeys()
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
        return $this->computedKeys()->flip()->has($key)
            && method_exists($this, Str::camel($key));
    }
}
