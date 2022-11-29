<?php

namespace Aerni\AdvancedSeo\GraphQL\Queries;

use Statamic\Facades\GraphQL;
use GraphQL\Type\Definition\Type;
use Statamic\GraphQL\Queries\Query;
use Aerni\AdvancedSeo\GraphQL\Types\SiteDefaultsType;

class SeoSiteDefaultsQuery extends Query
{
    protected $attributes = [
        'name' => 'seoSiteDefaults',
    ];

    public function type(): Type
    {
        return GraphQL::type(SiteDefaultsType::NAME);
    }

    public function args(): array
    {
        return [
            'site' => GraphQL::string(),
        ];
    }

    public function resolve($root, $args): array
    {
        return $args;
    }
}
