<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Fields\ContentSeoSetLocalizationFields;

class ContentSeoSetLocalizationBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'content_localization';
    }

    protected function tabs(): array
    {
        return [
            'main' => ContentSeoSetLocalizationFields::class,
        ];
    }
}
