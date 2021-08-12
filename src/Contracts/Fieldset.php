<?php

namespace Aerni\AdvancedSeo\Contracts;

interface Fieldset
{
    public function for(mixed $data): self;

    public function contents(): ?array;
}
