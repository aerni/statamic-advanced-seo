<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Blueprints\GeneralBlueprint;
use Aerni\AdvancedSeo\Data\SeoSetLocalization;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Type;

class GeneralDefaultsType extends Type
{
    const NAME = 'generalDefaults';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        return GeneralBlueprint::definition()->fields()->toGql()
            ->map(function ($field, $handle) {
                $field['resolve'] = $this->resolver();

                return $field;
            })->all();
    }

    private function resolver(): callable
    {
        return function (SeoSetLocalization $variables, $args, $context, ResolveInfo $info) {
            return $variables->resolveGqlValue($info->fieldName);
        };
    }
}
