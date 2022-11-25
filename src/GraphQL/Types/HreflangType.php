<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;

class HreflangType extends Type
{
    const NAME = 'Hreflang';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        return [
            'url' => [
                'type' => GraphQl::string(),
                'resolve' => fn ($hreflang) => $hreflang['url'],
            ],
            'locale' => [
                'type' => GraphQl::string(),
                'resolve' => fn ($hreflang) => $hreflang['locale'],
            ],
        ];
    }
}