<?php

namespace Aerni\AdvancedSeo\GraphQL\Queries;

use Statamic\Facades\GraphQL;
use GraphQL\Type\Definition\Type;
use Statamic\GraphQL\Queries\Query;
use Aerni\AdvancedSeo\GraphQL\Types\SeoDefaultsType;

class SeoDefaultsQuery extends Query
{
    protected $attributes = [
        'name' => 'seoDefaults',
        'description' => 'The Advanced SEO site, collection, and taxonomy defaults',
    ];

    public function type(): Type
    {
        return GraphQL::type(SeoDefaultsType::NAME);
    }

    public function resolve($root, $args)
    {
        return $args;
    }
}
