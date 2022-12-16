<?php

namespace Aerni\AdvancedSeo\GraphQL\Queries;

use Aerni\AdvancedSeo\GraphQL\Types\SeoDefaultsType;
use GraphQL\Type\Definition\Type;
use Statamic\Facades\GraphQL;
use Statamic\GraphQL\Queries\Query;

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
}
