<?php

namespace Aerni\AdvancedSeo\GraphQL\Queries;

use Aerni\AdvancedSeo\GraphQL\Types\SiteDefaultsType;
use GraphQL\Type\Definition\Type;
use Statamic\Facades\GraphQL;
use Statamic\GraphQL\Queries\Query;

class SeoSiteDefaultsQuery extends Query
{
    protected $attributes = [
        'name' => 'seoSiteDefaults',
        'description' => 'Use this query to get the Advanced SEO site defaults',
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
