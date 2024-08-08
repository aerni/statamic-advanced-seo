<?php

namespace Aerni\AdvancedSeo\GraphQL\Fields;

use Statamic\Facades\GraphQL;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Field;
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
        return GraphQL::listOf(GraphQL::type(SeoSitemapType::NAME));
    }

    public function resolve($root, $args, $context, ResolveInfo $info): ?array
    {
        $sitemaps = Sitemap::all()
            ->filter(fn ($sitemap) => $sitemap->type() === $info->fieldName)
            ->when(
                $handle = data_get($args, 'handle'),
                fn ($sitemaps) => $sitemaps->filter(fn ($sitemap) => $sitemap->handle() === $handle)
            );

        $urls = $sitemaps->flatMap->urls()
            ->when(
                $site = data_get($args, 'site'),
                fn ($urls) => $urls->filter(fn ($url) => $url->site() === $site)
            )
            ->when(
                $baseUrl = data_get($args, 'baseUrl'),
                fn ($urls) => $urls->each(fn ($url) => $url->baseUrl($baseUrl))
            );

        return $urls->isNotEmpty() ? $urls->toArray() : null;
    }
}
