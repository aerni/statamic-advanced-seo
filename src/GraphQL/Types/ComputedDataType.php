<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Collection;
use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;
use Statamic\Fields\Value;
use Statamic\GraphQL\Types\AssetInterface;

class ComputedDataType extends Type
{
    const NAME = 'ComputedData';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        $fields = [
            'title' => [
                'type' => GraphQl::string(),
            ],
            'og_image' => [
                'type' => GraphQl::type(AssetInterface::NAME),
            ],
            'og_image_preset' => [
                'type' => GraphQl::type(SocialImagePresetType::NAME),
            ],
            'twitter_image' => [
                'type' => GraphQl::type(AssetInterface::NAME),
            ],
            'twitter_image_preset' => [
                'type' => GraphQl::type(SocialImagePresetType::NAME),
            ],
            'twitter_handle' => [
                'type' => GraphQl::string(),
            ],
            'indexing' => [
                'type' => GraphQl::string(),
            ],
            'locale' => [
                'type' => GraphQl::string(),
            ],
            'hreflang' => [
                'type' => GraphQl::listOf(GraphQL::type(HreflangType::NAME)),
            ],
            'canonical' => [
                'type' => GraphQl::string(),
            ],
            'schema' => [
                'type' => GraphQl::string(),
            ],
            'breadcrumbs' => [
                'type' => GraphQl::string(),
            ],
        ];

        return collect($fields)->map(function ($field, $handle) {
            $field['resolve'] = $this->resolver();

            return $field;
        })->all();
    }

    private function resolver(): callable
    {
        return function (Collection $computedData, $args, $context, ResolveInfo $info) {
            $value = $computedData->get($info->fieldName);

            return $value instanceof Value ? $value->value() : $value;
        };
    }
}
