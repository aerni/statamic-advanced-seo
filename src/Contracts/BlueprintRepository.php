<?php

namespace Aerni\AdvancedSeo\Contracts;

interface BlueprintRepository
{
    public function seoEntryContents(): array;

    public function seoGlobalsContents(): array;
}
