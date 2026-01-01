<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Blueprints\IndexingBlueprint;
use Aerni\AdvancedSeo\Data\SeoSetLocalization;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Type;

class IndexingDefaultsType extends Type
{
    const NAME = 'indexingDefaults';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        return IndexingBlueprint::make()->get()->fields()->toGql()
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
