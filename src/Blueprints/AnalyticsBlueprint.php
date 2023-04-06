<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Fields\AnalyticsFields;

class AnalyticsBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'analytics';
    }

    protected function tabs(): array
    {
        return [
            'analytics' => AnalyticsFields::class,
        ];
    }
}
