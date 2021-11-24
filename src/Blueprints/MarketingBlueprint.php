<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Fields\TrackersFields;

class MarketingBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'marketing';
    }

    protected function sections(): array
    {
        return [
            'trackers' => TrackersFields::class,
        ];
    }
}
