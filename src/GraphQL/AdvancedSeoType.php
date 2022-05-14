<?php

namespace Aerni\AdvancedSeo\GraphQL;

use Illuminate\Support\Arr;
use Statamic\Facades\GraphQL;
use Statamic\GraphQL\Types\ArrayType;
use Statamic\GraphQL\Types\AssetInterface;

class AdvancedSeoType extends \Rebing\GraphQL\Support\Type
{
    const NAME = 'AdvancedSeo';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        return [
            'title' => [
                'type' => GraphQL::string(),
                'description' => 'The meta title of this entry.',
            ],
            'description' => [
                'type' => GraphQL::string(),
                'description' => 'The meta description of this entry.',
            ],
            // 'og_image' => [
            //     'type' => GraphQL::type(AssetInterface::NAME),
            //     'description' => 'The Open Graph image of this entry.',
            //     'resolve' => fn ($value) => Arr::get($value, 'og_image')?->value(),
            // ],
            // 'og_image_size' => [
            //     'type' => GraphQL::type(ArrayType::NAME),
            //     'description' => 'The Open Graph image size of this entry.',
            // ],
            // 'og_title' => [
            //     'type' => GraphQL::string(),
            //     'description' => 'The Open Graph title of this entry.',
            // ],
            // 'og_description' => [
            //     'type' => GraphQL::string(),
            //     'description' => 'The Open Graph description of this entry.',
            // ],
            // 'twitter_image' => [
            //     'type' => GraphQL::type(AssetInterface::NAME),
            //     'description' => 'The Twitter image of this entry.',
            //     'resolve' => fn ($value) => Arr::get($value, 'twitter_image')?->value(),
            // ],
            // 'twitter_image_size' => [
            //     'type' => GraphQL::type(ArrayType::NAME),
            //     'description' => 'The Twitter image size of this entry.',
            // ],
            // 'twitter_title' => [
            //     'type' => GraphQL::string(),
            //     'description' => 'The Twitter title of this entry.',
            // ],
            // 'twitter_description' => [
            //     'type' => GraphQL::string(),
            //     'description' => 'The Twitter description of this entry.',
            // ],
            // 'twitter_card' => [
            //     'type' => GraphQL::string(),
            //     'description' => 'The Twitter card of this entry.',
            // ],
            // 'indexing' => [
            //     'type' => GraphQL::string(),
            //     'description' => 'The indexing options of this entry.',
            // ],
            // 'locale' => [
            //     'type' => GraphQL::string(),
            //     'description' => 'The locale of this entry.',
            // ],
            // 'hreflang' => [
            //     'type' => GraphQL::type(ArrayType::NAME),
            //     'description' => 'The alternate locales of this entry.',
            // ],
            // // TODO: This should probably be part of a site type and not on the entry?
            // 'twitter_handle' => [
            //     'type' => GraphQL::string(),
            //     'description' => 'The Twitter handle of this site.',
            // ],
            // // TODO: This should probably be part of a site type and not on the entry?
            // 'site_name' => [
            //     'type' => GraphQL::string(),
            //     'description' => 'The name of this site.',
            // ],
        ];
    }
}
