<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Type;
use Aerni\AdvancedSeo\Data\SeoVariables;
use GraphQL\Type\Definition\ResolveInfo;
use Aerni\AdvancedSeo\Blueprints\AnalyticsBlueprint;

class AnalyticsDefaultsType extends Type
{
    const NAME = 'analyticsDefaults';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        return AnalyticsBlueprint::make()->get()->fields()->toGql()
            ->filter(fn ($field, $handle) => ! Str::contains($handle, 'section_')) // Remove all section fields, as they don't have any data anyways.
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
