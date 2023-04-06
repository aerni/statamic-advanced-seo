<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Fields\OnPageSeoFields;

class OnPageSeoBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'content';
    }

    protected function tabs(): array
    {
        return [
            'seo' => OnPageSeoFields::class,
        ];
    }
}
