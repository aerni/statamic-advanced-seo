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
            ],
            'handle' => [
                'type' => GraphQL::string(),
            ],
            'type' => [
                'type' => GraphQL::string(),
            ],
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
        $sitemaps = Sitemap::all()
            ->each(fn ($sitemap) => $sitemap->baseUrl($args['baseUrl'] ?? null));

        if ($handle = $args['handle'] ?? null) {
            $sitemaps = $sitemaps->filter(fn ($sitemap) => $sitemap->handle() === $handle);
        }

        if ($type = $args['type'] ?? null) {
            $sitemaps = $sitemaps->filter(fn ($sitemap) => $sitemap->type() === $type);
        }

        $sitemapUrls = $sitemaps->flatMap->urls();

        if ($site = $args['site'] ?? null) {
            $sitemapUrls = $sitemapUrls->where('site', $site);
        }

        return $sitemapUrls->isNotEmpty() ? $sitemapUrls : null;
    }
}
