<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;

class SitemapUrlType extends Type
{
    const NAME = 'sitemapUrl';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'A URL entry in a sitemap',
    ];

    public function fields(): array
    {
        return [
            'loc' => [
                'type' => GraphQL::string(),
                'description' => 'The URL location',
                'resolve' => fn (array $url) => $url['loc'],
            ],
            'lastmod' => [
                'type' => GraphQL::string(),
                'description' => 'The last modification date',
                'resolve' => fn (array $url) => $url['lastmod'],
            ],
            'changefreq' => [
                'type' => GraphQL::string(),
                'description' => 'How frequently the page is likely to change',
                'resolve' => fn (array $url) => $url['changefreq'],
            ],
            'priority' => [
                'type' => GraphQL::string(),
                'description' => 'The priority of this URL relative to other URLs',
                'resolve' => fn (array $url) => $url['priority'],
            ],
            'alternates' => [
                'type' => GraphQL::listOf(GraphQL::type(SitemapAlternatesType::NAME)),
                'description' => 'Alternate language versions of this URL',
                'resolve' => fn (array $url) => $url['alternates'],
            ],
        ];
    }
}
