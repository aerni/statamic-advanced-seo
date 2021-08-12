<?php

namespace Aerni\AdvancedSeo\Contracts;

interface Blueprint
{
    public function for(mixed $data): self;

    public function contents(): array;
}
