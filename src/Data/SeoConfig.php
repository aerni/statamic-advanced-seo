<?php

namespace Aerni\AdvancedSeo\Data;

use Illuminate\Support\Collection;

class SeoConfig
{
    protected Collection $data;

    public function __construct(array $data = [])
    {
        $this->data = collect($data);
    }

    public function get(string $key, ?string $default = null): mixed
    {
        return $this->data->get($key, $default);
    }

    public function has(string $key): bool
    {
        return $this->data->has($key);
    }

    public function set($key, $value): self
    {
        $this->data->put($key, $value);

        return $this;
    }

    public function remove(string $key): self
    {
        $this->data->forget($key);

        return $this;
    }

    public function data(?array $data = null): Collection|self
    {
        if (func_num_args() === 0) {
            return $this->data;
        }

        $this->data = collect($data);

        return $this;
    }

    public function merge(array $data): self
    {
        $this->data = $this->data->merge($data);

        return $this;
    }
}
