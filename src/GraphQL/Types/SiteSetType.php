<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Data\SeoSetLocalization;
use Aerni\AdvancedSeo\GraphQL\Resolvers\SeoSetResolver;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;

class SiteSetType extends Type
{
    const NAME = 'siteSet';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The SEO set for the site',
    ];

    public function fields(): array
    {
        return [
            'analytics' => [
                'type' => GraphQL::type(AnalyticsSiteSetType::NAME),
                'description' => 'The analytics settings',
                'resolve' => $this->resolver(),
            ],
            'favicons' => [
                'type' => GraphQL::type(FaviconsSiteSetType::NAME),
                'description' => 'The favicons settings',
                'resolve' => $this->resolver(),
            ],
            'general' => [
                'type' => GraphQL::type(GeneralSiteSetType::NAME),
                'description' => 'The general SEO settings',
                'resolve' => $this->resolver(),
            ],
            'indexing' => [
                'type' => GraphQL::type(IndexingSiteSetType::NAME),
                'description' => 'The indexing settings',
                'resolve' => $this->resolver(),
            ],
            'socialMedia' => [
                'type' => GraphQL::type(SocialMediaSiteSetType::NAME),
                'description' => 'The social media settings',
                'resolve' => $this->resolver(),
            ],
        ];
    }

    private function resolver(): callable
    {
        return function ($root, $args, $context, ResolveInfo $info): ?SeoSetLocalization {
            return SeoSetResolver::resolve('site::'.Str::snake($info->fieldName), $root['site'] ?? null);
        };
    }
}
