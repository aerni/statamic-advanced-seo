<?php

namespace Aerni\AdvancedSeo\Contracts;

use Illuminate\Support\Collection;

interface FieldsetRepository
{
    public function find(string $handle): Collection;
}
