<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Fields\ContentDefaultsFields;

class ContentDefaultsBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'content';
    }

    protected function sections(): array
    {
        return [
            'main' => ContentDefaultsFields::class,
        ];
    }
}
