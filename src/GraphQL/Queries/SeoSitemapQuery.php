<?php

namespace Aerni\AdvancedSeo\GraphQL\Queries;

use Aerni\AdvancedSeo\Facades\Sitemap;
use Aerni\AdvancedSeo\GraphQL\Types\SeoSitemapType;
use GraphQL\Type\Definition\Type;
use Statamic\Facades\GraphQL;
use Statamic\GraphQL\Queries\Query;

class SeoSitemapQuery extends Query
{
    protected $attributes = [
        'name' => 'seoSitemap',
        'description' => 'The Advanced SEO sitemap',
    ];

    public function type(): Type
    {
        return GraphQL::listOf(GraphQL::type(SeoSitemapType::NAME));
    }

    public function resolve($root, $args)
    {
        // TODO: Don't return anything if sitemaps are disabled?
        // throw_unless(config('advanced-seo.sitemap.enabled'), new NotFoundHttpException);

        return Sitemap::all()->flatMap->urls();
    }
}
