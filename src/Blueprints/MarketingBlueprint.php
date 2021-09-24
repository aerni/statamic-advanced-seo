<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Fields\IndexingFields;
use Aerni\AdvancedSeo\Fields\SiteVerificationFields;
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
            'indexing' => IndexingFields::class,
            'site_verification' => SiteVerificationFields::class,
        ];
    }
}