<?php

namespace Aerni\AdvancedSeo\Data;

use Illuminate\Support\Collection;

class DefaultsData
{
    public function __construct(
        public string $type,
        public string $handle,
        public ?string $locale = null,
        public ?Collection $sites = null,
    ) {
    }

    public function id(): string
    {
        return "{$this->type}::{$this->handle}::{$this->locale}";
    }
}
