<?php

namespace Aerni\AdvancedSeo\GraphQL\Enums;

use Rebing\GraphQL\Support\EnumType;

class SitemapTypeEnum extends EnumType
{
    const NAME = 'sitemapType';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The type of sitemap',
    ];

    public function values(): array
    {
        return [
            'COLLECTION' => [
                'value' => 'collection',
                'description' => 'Collection sitemap',
            ],
            'TAXONOMY' => [
                'value' => 'taxonomy',
                'description' => 'Taxonomy sitemap',
            ],
            'CUSTOM' => [
                'value' => 'custom',
                'description' => 'Custom sitemap',
            ],
        ];
    }

    public function getAttributes(): array
    {
        return array_merge($this->attributes, [
            'values' => $this->values(),
        ]);
    }
}
