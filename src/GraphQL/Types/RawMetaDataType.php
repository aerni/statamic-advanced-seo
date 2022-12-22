<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Collection;
use Rebing\GraphQL\Support\Type;
use Statamic\Support\Str;

class RawMetaDataType extends Type
{
    const NAME = 'rawMetaData';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The Advanced SEO raw meta data',
    ];

    public function fields(): array
    {
        return OnPageSeoBlueprint::make()->get()->fields()->toGql()
            ->mapWithKeys(fn ($field, $handle) => [Str::remove('seo_', $handle) => $field]) // We want to remove `seo_` from all the field keys
            ->filter(fn ($field, $handle) => ! Str::contains($handle, 'section_')) // Remove all section fields, as they don't have any data anyways.
            ->map(function ($field, $handle) {
                $field['resolve'] = $this->resolver();

                return $field;
            })->all();
    }

    private function resolver(): callable
    {
        return function (Collection $values, $args, $context, ResolveInfo $info) {
            return $values->get("seo_{$info->fieldName}");
        };
    }
}
