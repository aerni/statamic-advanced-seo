<?php

namespace Aerni\AdvancedSeo\Contracts;

interface BlueprintSection
{
    public function contents(): array;

    public function fields(): array;
}
