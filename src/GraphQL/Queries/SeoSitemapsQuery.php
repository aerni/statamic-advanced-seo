<?php

namespace Aerni\AdvancedSeo\GraphQL\Queries;

use Aerni\AdvancedSeo\Facades\Sitemap;
use Aerni\AdvancedSeo\GraphQL\Enums\SitemapTypeEnum;
use Aerni\AdvancedSeo\GraphQL\Types\SitemapType;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
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
        return GraphQL::listOf(GraphQL::type(SitemapType::NAME));
    }

    public function args(): array
    {
        return [
            'site' => [
                'type' => GraphQL::nonNull(GraphQL::string()),
                'description' => 'A site handle. Returns sitemaps for all sites sharing that site\'s domain.',
            ],
            'type' => [
                'type' => GraphQL::type(SitemapTypeEnum::NAME),
                'description' => 'Filter by sitemap type',
            ],
            'handle' => [
                'type' => GraphQL::string(),
                'description' => 'Filter by collection, taxonomy, or custom sitemap handle',
            ],
        ];
    }

    public function resolve($root, $args): ?Collection
    {
        return Sitemap::index($args['site'])
            ?->sitemaps()
            ->when($args['type'] ?? null, fn ($sitemaps, $type) => $sitemaps->filter(fn ($sitemap) => $sitemap->type() === $type))
            ->when($args['handle'] ?? null, fn ($sitemaps, $handle) => $sitemaps->filter(fn ($sitemap) => $sitemap->handle() === $handle));
    }
}
