<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Fields\GeneralFields;

class GeneralBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'general';
    }

    protected function sections(): array
    {
        return [
            'general' => GeneralFields::class,
        ];
    }
}
