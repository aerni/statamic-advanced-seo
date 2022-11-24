<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Aerni\AdvancedSeo\Models\Defaults;
use Aerni\AdvancedSeo\View\GraphQlCascade;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;
use Statamic\Support\Str;

class MetaType extends Type
{
    const NAME = 'AdvancedSeoView';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        return [
            'head' => [
                'type' => GraphQL::string(),
                'resolve' => fn (GraphQlCascade $cascade) => view("advanced-seo::head", $cascade->all()),
            ],
            'body' => [
                'type' => GraphQL::string(),
                'resolve' => fn (GraphQlCascade $cascade) => view("advanced-seo::body", $cascade->all()),
            ],
        ];
    }

    private function resolver(): callable
    {
        return function (GraphQlCascade $cascade, $args, $context, ResolveInfo $info) {
            return $cascade->value($info->fieldName);
        };
    }
}
