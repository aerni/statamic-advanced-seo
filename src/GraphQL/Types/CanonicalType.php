<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;

class CanonicalType extends Type
{
    const NAME = 'canonical';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        return [
            'permalink' => [
                'type' => GraphQl::string(),
                'resolve' => fn (array $canonical) => $canonical['permalink'],
            ],
            'url' => [
                'type' => GraphQl::string(),
                'resolve' => fn (array $canonical) => $canonical['url'],
            ],
        ];
    }
}
