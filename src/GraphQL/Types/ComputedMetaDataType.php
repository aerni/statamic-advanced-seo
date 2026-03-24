<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Cascades\ContentCascade;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;

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
            'og_image_preset' => [
                'type' => GraphQL::type(SocialImagePresetType::NAME),
                'resolve' => $this->resolver(),
            ],
            'twitter_image_preset' => [
                'type' => GraphQL::type(SocialImagePresetType::NAME),
                'resolve' => $this->resolver(),
            ],
            'twitter_card' => [
                'type' => GraphQL::string(),
                'resolve' => $this->resolver(),
            ],
            'twitter_handle' => [
                'type' => GraphQL::string(),
                'resolve' => $this->resolver(),
            ],
            'indexing' => [
                'type' => GraphQL::string(),
                'resolve' => $this->resolver(),
            ],
            'locale' => [
                'type' => GraphQL::string(),
                'resolve' => $this->resolver(),
            ],
            'hreflang' => [
                'type' => GraphQL::listOf(GraphQL::type(HreflangType::NAME)),
                'resolve' => $this->resolver(),
            ],
            'canonical' => [
                'type' => GraphQL::string(),
                'resolve' => $this->resolver(),
            ],
            'site_schema' => [
                'type' => GraphQL::string(),
                'resolve' => $this->resolver(),
            ],
            'breadcrumbs' => [
                'type' => GraphQL::string(),
                'resolve' => $this->resolver(),
            ],
        ];
    }

    private function resolver(): callable
    {
        return fn (ContentCascade $cascade, array $args, mixed $context, ResolveInfo $info) => $cascade->{$info->fieldName};
    }
}
