<?php

namespace Aerni\AdvancedSeo\Fields;

abstract class BaseFields
{
    protected mixed $data;

    public function __construct(mixed $data)
    {
        $this->data = $data;
    }

    public static function data(mixed $data): self
    {
        return new static($data);
    }

    public function getConfig(): array
    {
        return array_flatten($this->sections(), 1);
    }

    abstract public function sections(): array;
}
