<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\View\GraphQlCascade;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;
use Statamic\GraphQL\Types\AssetInterface;

class ComputedMetaDataType extends Type
{
    const NAME = 'computedMetaData';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The Advanced SEO computed meta data',
    ];

    public function fields(): array
    {
        return [
            'site_name' => [
                'type' => GraphQl::string(),
                'resolve' => $this->resolver(),
            ],
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
                'args' => $this->args(),
                'resolve' => $this->resolver(),
            ],
            'canonical' => [
                'type' => GraphQl::string(),
                'args' => $this->args(),
                'resolve' => $this->resolver(),
            ],
            'site_schema' => [
                'type' => GraphQl::string(),
                'args' => $this->args(),
                'resolve' => $this->resolver(),
            ],
            'breadcrumbs' => [
                'type' => GraphQl::string(),
                'args' => $this->args(),
                'resolve' => $this->resolver(),
            ],
        ];
    }

    private function resolver(): callable
    {
        return function (GraphQlCascade $cascade, $args, $context, ResolveInfo $info) {
            return $cascade->baseUrl($args['baseUrl'] ?? null)->{$info->fieldName};
        };
    }

    private function args(): array
    {
        return [
            'baseUrl' => [
                'name' => 'baseUrl',
                'description' => 'Change the base URL if your frontend is hosted on another domain than Statamic',
                'type' => GraphQL::string(),
                'rules' => ['url'],
            ],
        ];
    }
}
