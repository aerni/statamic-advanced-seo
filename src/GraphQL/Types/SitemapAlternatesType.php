<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;

class SitemapAlternatesType extends Type
{
    const NAME = 'sitemapAlternates';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        return [
            'href' => [
                'type' => GraphQL::string(),
                'resolve' => fn (array $alternate) => $alternate['href'],
            ],
            'hreflang' => [
                'type' => GraphQL::string(),
                'resolve' => fn (array $alternate) => $alternate['hreflang'],
            ],
        ];
    }
}
