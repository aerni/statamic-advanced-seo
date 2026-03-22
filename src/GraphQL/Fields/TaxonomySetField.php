<?php

namespace Aerni\AdvancedSeo\GraphQL\Fields;

use Aerni\AdvancedSeo\SeoSets\SeoSetLocalization;
use Aerni\AdvancedSeo\GraphQL\Resolvers\SeoSetResolver;
use Aerni\AdvancedSeo\GraphQL\Types\TaxonomySetType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Field;
use Statamic\Facades\GraphQL;

class TaxonomySetField extends Field
{
    protected $attributes = [
        'description' => 'The SEO defaults for a taxonomy',
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
        return GraphQL::type(TaxonomySetType::NAME);
    }

    protected function resolve($root, $args, $context, ResolveInfo $info): ?SeoSetLocalization
    {
        return SeoSetResolver::resolve('taxonomies::'.$args['handle'], $args['site'] ?? null);
    }
}
