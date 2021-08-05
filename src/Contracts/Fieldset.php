<?php

namespace Aerni\AdvancedSeo\Contracts;

use Illuminate\Support\Collection;

interface Fieldset
{
    public function contents(): ?array;

    public function fields(): Collection;
}
