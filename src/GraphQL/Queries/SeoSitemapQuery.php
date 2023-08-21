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

    public function args(): array
    {
        return [
            'site' => [
                'type' => GraphQL::string(),
            ],
        ];
    }

    public function type(): Type
    {
        return GraphQL::listOf(GraphQL::type(SeoSitemapType::NAME));
    }

    public function resolve($root, $args)
    {
        $sitemapUrls = Sitemap::all()->flatMap->urls();

        if ($site = $args['site'] ?? null) {
            $sitemapUrls = $sitemapUrls->where('site', $site);
        }

        return $sitemapUrls;
    }
}
