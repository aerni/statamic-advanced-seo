<?php

namespace Aerni\AdvancedSeo\Registries;

use Illuminate\Support\Collection;
use Statamic\Facades\Blink;

abstract class Registry
{
    abstract protected function items(): array;

    public function all(): Collection
    {
        return Blink::once(static::class.'::all', fn () => collect($this->items()));
    }
}
