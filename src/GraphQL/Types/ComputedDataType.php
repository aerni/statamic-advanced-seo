<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\View\GraphQlCascade;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;
use Statamic\GraphQL\Types\AssetInterface;

class ComputedDataType extends Type
{
    const NAME = 'computedData';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The computed Advanced SEO fields like the `title`, `hreflang`, or `indexing`',
    ];

    public function fields(): array
    {
        return [
            'title' => [
                'type' => GraphQl::string(),
                'resolve' => $this->resolver(),
            ],
            'og_image' => [
                'type' => GraphQl::type(AssetInterface::NAME),
                'resolve' => $this->resolver(),
            ],
            'og_image_preset' => [
                'type' => GraphQl::type(SocialImagePresetType::NAME),
                'resolve' => $this->resolver(),
            ],
            'og_title' => [
                'type' => GraphQl::string(),
                'resolve' => $this->resolver(),
            ],
            'twitter_image' => [
                'type' => GraphQl::type(AssetInterface::NAME),
                'resolve' => $this->resolver(),
            ],
            'twitter_image_preset' => [
                'type' => GraphQl::type(SocialImagePresetType::NAME),
                'resolve' => $this->resolver(),
            ],
            'twitter_title' => [
                'type' => GraphQl::string(),
                'resolve' => $this->resolver(),
            ],
            'twitter_handle' => [
                'type' => GraphQl::string(),
                'resolve' => $this->resolver(),
            ],
            'indexing' => [
                'type' => GraphQl::string(),
                'resolve' => $this->resolver(),
            ],
            'locale' => [
                'type' => GraphQl::string(),
                'resolve' => $this->resolver(),
            ],
            'hreflang' => [
                'type' => GraphQl::listOf(GraphQL::type(HreflangType::NAME)),
                'resolve' => $this->resolver(),
            ],
            'canonical' => [
                'type' => GraphQl::type(CanonicalType::NAME),
                'resolve' => $this->resolver(),
            ],
            'site_schema' => [
                'type' => GraphQl::string(),
                'resolve' => $this->resolver(),
            ],
            'breadcrumbs' => [
                'type' => GraphQl::string(),
                'resolve' => $this->resolver(),
            ],
        ];
    }

    private function resolver(): callable
    {
        return function (GraphQlCascade $cascade, $args, $context, ResolveInfo $info) {
            return $cascade->{$info->fieldName};
        };
    }
}
