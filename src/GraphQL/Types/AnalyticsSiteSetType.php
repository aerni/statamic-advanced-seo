<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Blueprints\AnalyticsBlueprint;

class AnalyticsSiteSetType extends BaseSiteSetType
{
    const NAME = 'analyticsSiteSet';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The analytics settings',
    ];

    protected function blueprint(): string
    {
        return AnalyticsBlueprint::class;
    }
}
