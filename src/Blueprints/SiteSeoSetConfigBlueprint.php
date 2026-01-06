<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Fields\SiteSeoSetConfigFields;

class SiteSeoSetConfigBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'site_config';
    }

    protected function tabs(): array
    {
        return [
            'main' => SiteSeoSetConfigFields::class,
        ];
    }
}
