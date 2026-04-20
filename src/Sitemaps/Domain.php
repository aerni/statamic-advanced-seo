<?php

namespace Aerni\AdvancedSeo\Sitemaps;

use Illuminate\Support\Collection;
use Stringable;

class Domain implements Stringable
{
    public function __construct(
        public string $name,
        public Collection $sites,
    ) {}

    public function __toString(): string
    {
        return $this->name;
    }
}
