<?php

namespace Aerni\AdvancedSeo\GraphQL\Fields;

use Aerni\AdvancedSeo\GraphQL\Types\SiteSetType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Field;
use Statamic\Facades\GraphQL;

class SiteSetField extends Field
{
    protected $attributes = [
        'description' => 'The site SEO set',
    ];

    public function args(): array
    {
        return [
            'site' => [
                'type' => GraphQL::string(),
            ],
        ];
    }

    public function type(): Type
    {
        return GraphQL::type(SiteSetType::NAME);
    }

    protected function resolve($root, $args, $context, ResolveInfo $info): array
    {
        return $args;
    }
}
