<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Str;
use Statamic\Contracts\GraphQL\ResolvesValues;

class OnPageSeoType extends \Rebing\GraphQL\Support\Type
{
    const NAME = 'AdvancedSeo';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        $fields = OnPageSeoBlueprint::make()->get()->fields()->toGql()
            ->filter(fn ($field, $handle) => ! Str::contains($handle, 'section_')) // We don't want to expose the content of section fields
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
            ->map(function ($field, $handle) {
                $field['resolve'] = $field['resolve'] ?? $this->resolver();

                return $field;
            })
            ->all();

        return $fields;
    }

    private function resolver()
    {
        return function (ResolvesValues $entry, $args, $context, ResolveInfo $info) {
            return $entry->resolveGqlValue($info->fieldName);
        };
    }
}
