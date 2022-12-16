<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Blueprints\ContentDefaultsBlueprint;
use Aerni\AdvancedSeo\Data\SeoVariables;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Type;
use Statamic\Support\Str;

class ContentDefaultsType extends Type
{
    const NAME = 'contentDefaults';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The Advanced SEO collection and taxonomy defaults'
    ];

    public function fields(): array
    {
        return ContentDefaultsBlueprint::make()->get()->fields()->toGql()
            ->mapWithKeys(fn ($field, $handle) => [Str::remove('seo_', $handle) => $field]) // We want to remove `seo_` from all the field keys
            ->filter(fn ($field, $handle) => ! Str::contains($handle, 'section_')) // Remove all section fields, as they don't have any data anyways.
            ->map(function ($field, $handle) {
                $field['resolve'] = $this->resolver();

                return $field;
            })->all();
    }

    private function resolver(): callable
    {
        return function (SeoVariables $variables, $args, $context, ResolveInfo $info) {
            return $variables->resolveGqlValue("seo_{$info->fieldName}");
        };
    }
}
