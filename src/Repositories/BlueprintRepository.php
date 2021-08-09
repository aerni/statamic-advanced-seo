<?php

namespace Aerni\AdvancedSeo\Repositories;

use Aerni\AdvancedSeo\Contracts\BlueprintRepository as Contract;
use Facades\Aerni\AdvancedSeo\Blueprints\SeoEntryBlueprint;
use Facades\Aerni\AdvancedSeo\Blueprints\SeoGlobalsBlueprint;

class BlueprintRepository implements Contract
{
    public function seoEntryContents(): array
    {
        return SeoEntryBlueprint::contents();
    }

    public function seoGlobalsContents(): array
    {
        return SeoGlobalsBlueprint::contents();
    }
}
