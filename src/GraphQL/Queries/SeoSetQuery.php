<?php

namespace Aerni\AdvancedSeo\GraphQL\Queries;

use Aerni\AdvancedSeo\GraphQL\Types\SeoSetType;
use GraphQL\Type\Definition\Type;
use Statamic\Facades\GraphQL;
use Statamic\GraphQL\Queries\Query;

class SeoSetQuery extends Query
{
    protected $attributes = [
        'name' => 'seoSet',
        'description' => 'The Advanced SEO site, collection, and taxonomy sets',
    ];

    public function type(): Type
    {
        return GraphQL::type(SeoSetType::NAME);
    }

    public function resolve($root, $args)
    {
        return $args;
    }
}
