<?php

namespace Aerni\AdvancedSeo\GraphQL\Fields;

use Aerni\AdvancedSeo\GraphQL\Types\SeoSitemapType;
use Aerni\AdvancedSeo\Sitemap\SitemapIndex;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use Rebing\GraphQL\Support\Field;
use Statamic\Facades\GraphQL;

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
        $sitemaps = (new SitemapIndex)->{"{$info->fieldName}Sitemaps"}();

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
