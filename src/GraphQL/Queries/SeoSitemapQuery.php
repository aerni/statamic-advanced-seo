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
            'baseUrl' => [
                'type' => GraphQL::string(),
                'description' => 'Change the base URL if your frontend is hosted on another domain than Statamic',
                'rules' => ['url'],
            ],
            'handle' => [
                'type' => GraphQL::string(),
                'description' => 'Filter the results by the handle of a collection, taxonomy, or custom sitemap',
            ],
            'type' => [
                'type' => GraphQL::string(),
                'description' => 'Filter the results by type. Either `collection`, `taxonomy`, or `custom`.',
                'rules' => ['in:collection,taxonomy,custom'],
            ],
            'site' => [
                'type' => GraphQL::string(),
                'description' => 'Filter the results by site',
                'rules' => ['in:collection,taxonomy,custom'],
            ],
        ];
    }

    public function type(): Type
    {
        return GraphQL::listOf(GraphQL::type(SeoSitemapType::NAME));
    }

    public function resolve($root, $args)
    {
        $sitemaps = Sitemap::all()
            ->each(fn ($sitemap) => $sitemap->baseUrl($args['baseUrl'] ?? null));

        if ($handle = $args['handle'] ?? null) {
            $sitemaps = $sitemaps->filter(fn ($sitemap) => $handle === $sitemap->handle());
        }

        if ($type = $args['type'] ?? null) {
            $sitemaps = $sitemaps->filter(fn ($sitemap) => $type === $sitemap->type());
        }

        $sitemapUrls = $sitemaps->flatMap->urls();

        if ($site = $args['site'] ?? null) {
            $sitemapUrls = $sitemapUrls->where('site', $site);
        }

        return $sitemapUrls->isNotEmpty() ? $sitemapUrls : null;
    }
}
