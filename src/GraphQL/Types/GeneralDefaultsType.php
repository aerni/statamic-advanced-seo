<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Blueprints\GeneralBlueprint;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Type;
use Statamic\Fields\Value;

class GeneralDefaultsType extends Type
{
    const NAME = 'GeneralDefaults';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        return GeneralBlueprint::make()->get()->fields()->toGql()
            ->filter(fn ($field, $handle) => ! Str::contains($handle, 'section_')) // Remove all section fields, as they don't have any data anyways.
            ->map(function ($field, $handle) {
                $field['resolve'] = $this->resolver();

                return $field;
            })->all();
    }

    private function resolver(): callable
    {
        return function (Collection $siteDefaults, $args, $context, ResolveInfo $info) {
            $value = $siteDefaults->get($info->fieldName);

            return $value instanceof Value ? $value->value() : $value;
        };
    }
}
