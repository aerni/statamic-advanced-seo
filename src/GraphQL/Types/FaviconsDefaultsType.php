<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Blueprints\FaviconsBlueprint;
use Aerni\AdvancedSeo\Data\SeoVariables;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Type;

class FaviconsDefaultsType extends Type
{
    const NAME = 'faviconsDefaults';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        return FaviconsBlueprint::make()->get()->fields()->toGql()
            ->map(function ($field, $handle) {
                $field['resolve'] = $this->resolver();

                return $field;
            })->all();
    }

    private function resolver(): callable
    {
        return function (SeoVariables $variables, $args, $context, ResolveInfo $info) {
            return $variables->resolveGqlValue($info->fieldName);
        };
    }
}
