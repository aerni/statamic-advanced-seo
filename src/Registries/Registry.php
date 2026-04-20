<?php

namespace Aerni\AdvancedSeo\Registries;

use Illuminate\Support\Collection;
use Statamic\Facades\Blink;

abstract class Registry
{
    protected string $collection = Collection::class;

    abstract protected function items(): array;

    public function all(): Collection
    {
        return Blink::once(static::class.'::all', fn () => new $this->collection($this->items()));
    }
}
