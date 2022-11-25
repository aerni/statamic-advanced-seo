<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Statamic\Fields\Value;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Type;
use Illuminate\Support\Collection;
use GraphQL\Type\Definition\ResolveInfo;
use Aerni\AdvancedSeo\Blueprints\IndexingBlueprint;

class IndexingDefaultsType extends Type
{
    const NAME = 'IndexingDefaults';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        return IndexingBlueprint::make()->get()->fields()->toGql()
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
