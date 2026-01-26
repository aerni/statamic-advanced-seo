<?php

namespace Aerni\AdvancedSeo\GraphQL\Fields;

use Aerni\AdvancedSeo\Data\SeoSetLocalization;
use Aerni\AdvancedSeo\GraphQL\Resolvers\SeoSetResolver;
use Aerni\AdvancedSeo\GraphQL\Types\CollectionSetType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Field;
use Statamic\Facades\GraphQL;

class CollectionSetField extends Field
{
    protected $attributes = [
        'description' => 'The SEO defaults for a collection',
    ];

    public function args(): array
    {
        return [
            'handle' => [
                'name' => 'handle',
                'type' => GraphQL::nonNull(GraphQL::string()),
            ],
            'site' => [
                'type' => GraphQL::string(),
            ],
        ];
    }

    public function type(): Type
    {
        return GraphQL::type(CollectionSetType::NAME);
    }

    protected function resolve($root, $args, $context, ResolveInfo $info): ?SeoSetLocalization
    {
        return SeoSetResolver::resolve('collections::'.$args['handle'], $args['site'] ?? null);
    }
}
