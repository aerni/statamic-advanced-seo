<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Fields\ContentSeoSetConfigFields;

class ContentSeoSetConfigBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'content_config';
    }

    protected function tabs(): array
    {
        return [
            'main' => ContentSeoSetConfigFields::class,
        ];
    }
}
