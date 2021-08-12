<?php

namespace Aerni\AdvancedSeo\Contracts;

interface BlueprintRepository
{
    public function for(mixed $data): self;

    public function contents(): ?array;
}
