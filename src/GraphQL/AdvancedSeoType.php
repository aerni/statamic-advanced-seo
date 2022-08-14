<?php

namespace Aerni\AdvancedSeo\GraphQL;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Aerni\AdvancedSeo\Facades\SocialImage;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Statamic\Contracts\GraphQL\ResolvesValues;
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
        $fields = OnPageSeoBlueprint::make()->get()->fields()->toGql()
            // ->merge([
            //     // TODO: Should we even do this or is the width and height that the AssetInterface provides enough?
            //     'og_image_size' => [
            //         'type' => GraphQL::type(ArrayType::NAME),
            //         'description' => 'The Open Graph image size of this entry.',
            //         'resolve' => function () {
            //             $model = SocialImage::findModel('open_graph');

            //             return [
            //                 'width' => $model['width'],
            //                 'height' => $model['height'],
            //             ];
            //         },
            //     ],
            // ])
            ->filter(fn ($field, $handle) => ! Str::startsWith($handle, 'seo_section')) // We don't want to expose the content of section fields
            ->mapWithKeys(function ($field, $handle) {
                $field['resolve'] = $this->resolver();

                /**
                 * There are some fields that have existing resolvers. Like defined in HasSelectOptions for instance.
                 * We want to be able to remove `seo_` from the field names, but then those existing resolvers won't work
                 * because they use `$info->fieldName` without adding `seo_` in front of it to make it work like in `$this->resolver`.
                 */
                // $field['resolve'] = $field['resolve'] ?? $this->resolver();

                return [Str::remove('seo_', $handle) => $field];
            })
            ->all();

        return $fields;

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

    protected function resolver()
    {
        return function (ResolvesValues $entry, $args, $context, ResolveInfo $info) {
            return $entry->resolveGqlValue("seo_{$info->fieldName}");
        };
    }
}
