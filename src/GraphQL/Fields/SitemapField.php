<?php

namespace Aerni\AdvancedSeo\GraphQL\Fields;

use Statamic\Facades\GraphQL;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Field;
use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Facades\Sitemap;
use GraphQL\Type\Definition\ResolveInfo;
use Aerni\AdvancedSeo\GraphQL\Types\SeoSitemapType;

class SitemapField extends Field
{
    protected $attributes = [
        'description' => 'The Advanced SEO collection, taxonomy, or custom sitemap',
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
            'site' => [
                'type' => GraphQL::string(),
                'description' => 'Filter the results by site',
            ],
        ];
    }

    public function type(): Type
    {
        return GraphQl::listOf(GraphQL::type(SeoSitemapType::NAME));
    }

    public function resolve($root, $args, $context, ResolveInfo $info): ?Collection
    {
        $sitemaps = Sitemap::{"{$info->fieldName}Sitemaps"}();

        if ($baseUrl = $args['baseUrl'] ?? null) {
            $sitemaps = $sitemaps->each(fn ($sitemap) => $sitemap->baseUrl($baseUrl));
        }

        if ($handle = $args['handle'] ?? null) {
            $sitemaps = $sitemaps->filter(fn ($sitemap) => $handle === $sitemap->handle());
        }

        $sitemapUrls = $sitemaps->flatMap->urls();

        if ($site = $args['site'] ?? null) {
            $sitemapUrls = $sitemapUrls->where('site', $site);
        }

        return $sitemapUrls->isNotEmpty() ? $sitemapUrls : null;
    }
}
