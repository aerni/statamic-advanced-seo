<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\GraphQL\Fields\SitemapField;
use Rebing\GraphQL\Support\Type;

class SeoSitemapsType extends Type
{
    const NAME = 'seoSitemaps';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The Advanced SEO sitemaps',
    ];

    public function fields(): array
    {
        return [
            'collection' => new SitemapField,
            'taxonomy' => new SitemapField,
            'custom' => new SitemapField,
        ];
    }
}
