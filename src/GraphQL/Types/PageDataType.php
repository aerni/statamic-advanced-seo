<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Statamic\Support\Str;
use Rebing\GraphQL\Support\Type;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use GraphQL\Type\Definition\ResolveInfo;
use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;

class PageDataType extends Type
{
    const NAME = 'pageData';

    protected $attributes = [
        'name' => self::NAME,
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
        return function (Entry|Term $model, $args, $context, ResolveInfo $info) {
            return $model->resolveGqlValue("seo_{$info->fieldName}");
        };
    }
}
