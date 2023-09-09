<?php

namespace Aerni\AdvancedSeo\GraphQL\Queries;

use Aerni\AdvancedSeo\GraphQL\Types\SeoSitemapsType;
use GraphQL\Type\Definition\Type;
use Statamic\Facades\GraphQL;
use Statamic\GraphQL\Queries\Query;

class SeoSitemapsQuery extends Query
{
    protected $attributes = [
        'name' => 'seoSitemaps',
        'description' => 'The Advanced SEO sitemaps',
    ];

    public function type(): Type
    {
        return GraphQL::type(SeoSitemapsType::NAME);
    }

    public function resolve($root, $args)
    {
        return $args;
    }
}
