<?php

namespace Aerni\AdvancedSeo\Concerns;

use Illuminate\Support\Collection;
use Statamic\Facades\Blink;

trait HasDefaultValues
{
    public function defaultValues(): Collection
    {
        return Blink::once("advanced-seo::{$this->id()}::defaultValues", function () {
            return collect($this->blueprint()->fields()->all())
                ->map->defaultValue()
                ->filter(fn ($value) => $value !== null);
        });
    }

    protected function getOriginFallbackValues(): Collection
    {
        return $this->defaultValues();
    }

    protected function getOriginFallbackValue(string $key): mixed
    {
        return $this->getOriginFallbackValues()->get($key);
    }
}
