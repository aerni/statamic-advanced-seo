<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Statamic\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;
use Aerni\AdvancedSeo\Models\Defaults;
use Aerni\AdvancedSeo\GraphQL\Types\FaviconsDefaultsType;
use Aerni\AdvancedSeo\GraphQL\Types\IndexingDefaultsType;
use Aerni\AdvancedSeo\GraphQL\Types\AnalyticsDefaultsType;
use Aerni\AdvancedSeo\GraphQL\Types\SocialImagePresetType;
use Aerni\AdvancedSeo\GraphQL\Types\SocialMediaDefaultsType;

class SiteDefaultsType extends Type
{
    const NAME = 'SiteDefaults';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        $fields = [
            'analytics' => [
                'type' => GraphQL::type(AnalyticsDefaultsType::NAME),
                'resolve' => fn ($siteDefaults) => $siteDefaults,
            ],
            'favicons' => [
                'type' => GraphQL::type(FaviconsDefaultsType::NAME),
                'resolve' => fn ($siteDefaults) => $siteDefaults,
            ],
            'general' => [
                'type' => GraphQL::type(GeneralDefaultsType::NAME),
                'resolve' => fn ($siteDefaults) => $siteDefaults,
            ],
            'indexing' => [
                'type' => GraphQL::type(IndexingDefaultsType::NAME),
                'resolve' => fn ($siteDefaults) => $siteDefaults,
            ],
            'socialMedia' => [
                'type' => GraphQL::type(SocialMediaDefaultsType::NAME),
                'resolve' => fn ($siteDefaults) => $siteDefaults,
            ],
        ];

        return collect($fields)
            ->filter(fn ($type, $key) => Defaults::isEnabled('site::'.snake_case($key))) // Remove field if default is disabled
            ->all();
    }
}
