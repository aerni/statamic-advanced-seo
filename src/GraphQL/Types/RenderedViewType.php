<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;

class RenderedViewType extends Type
{
    const NAME = 'RenderedView';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        return [
            'head' => [
                'type' => GraphQL::string(),
                'resolve' => fn ($cascade) => view("advanced-seo::head", $cascade),
            ],
            'body' => [
                'type' => GraphQL::string(),
                'resolve' => fn ($cascade) => view("advanced-seo::body", $cascade),
            ],
        ];
    }
}
