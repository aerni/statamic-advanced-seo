<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Contracts\Sitemap;
use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;

class SitemapType extends Type
{
    const NAME = 'sitemap';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'An Advanced SEO sitemap',
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => GraphQL::string(),
                'description' => 'The sitemap identifier (e.g., "collection-articles")',
                'resolve' => fn (Sitemap $sitemap) => $sitemap->id(),
            ],
            'type' => [
                'type' => GraphQL::string(),
                'description' => 'The sitemap type (e.g., "collection", "taxonomy", "custom")',
                'resolve' => fn (Sitemap $sitemap) => $sitemap->type(),
            ],
            'handle' => [
                'type' => GraphQL::string(),
                'description' => 'The handle of the collection, taxonomy, or custom sitemap',
                'resolve' => fn (Sitemap $sitemap) => $sitemap->handle(),
            ],
            'lastmod' => [
                'type' => GraphQL::string(),
                'description' => 'The last modification date of this sitemap',
                'resolve' => fn (Sitemap $sitemap) => $sitemap->lastmod(),
            ],
            'urls' => [
                'type' => GraphQL::listOf(GraphQL::type(SitemapUrlType::NAME)),
                'description' => 'The URLs in this sitemap',
                'resolve' => fn (Sitemap $sitemap) => $sitemap->urls()->toArray(),
            ],
        ];
    }
}
