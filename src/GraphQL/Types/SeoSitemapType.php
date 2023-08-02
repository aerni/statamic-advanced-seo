<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;

class SeoSitemapType extends Type
{
    const NAME = 'seoSitemap';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The Advanced SEO sitemap',
    ];

    public function fields(): array
    {
        return [
            'alternates' => [
                'type' => GraphQL::listOf(GraphQL::type(SitemapAlternatesType::NAME)),
                'description' => 'The Advanced SEO sitemap alternates',
                'resolve' => fn (array $sitemap) => $sitemap['alternates'], // TODO: Return null if empty. Should do this in the CollectionSitemapUrl class.
            ],
            'changefreq' => [
                'type' => GraphQL::string(),
                'description' => 'The Advanced SEO sitemap changefreq',
                'resolve' => fn (array $sitemap) => $sitemap['changefreq'],
            ],
            'lastmod' => [
                'type' => GraphQL::string(),
                'description' => 'The Advanced SEO sitemap lastmod',
                'resolve' => fn (array $sitemap) => $sitemap['lastmod'],
            ],
            'loc' => [
                'type' => GraphQL::string(),
                'description' => 'The Advanced SEO sitemap loc',
                'resolve' => fn (array $sitemap) => $sitemap['loc'],
            ],
            'priority' => [
                'type' => GraphQL::string(),
                'description' => 'The Advanced SEO sitemap priority',
                'resolve' => fn (array $sitemap) => $sitemap['priority'],
            ],
        ];
    }
}
