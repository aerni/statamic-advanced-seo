<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Fields\OnPageSeoFields;

class ContentDefaultsBlueprint extends BaseBlueprint
{
    protected function sections(): array
    {
        return [
            'main' => OnPageSeoFields::class,
        ];
    }
}
