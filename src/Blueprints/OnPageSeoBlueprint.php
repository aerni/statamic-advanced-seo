<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Fields\OnPageSeoFields;

class OnPageSeoBlueprint extends BaseBlueprint
{
    protected function sections(): array
    {
        return [
            'seo' => OnPageSeoFields::class,
        ];
    }
}
