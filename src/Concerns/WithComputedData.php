<?php

namespace Aerni\AdvancedSeo\Concerns;

use Illuminate\Support\Collection;

trait WithComputedData
{
    protected Collection $computedData;

    abstract public function processComputedData(): self;
}
