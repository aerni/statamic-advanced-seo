<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Aerni\AdvancedSeo\Models\Defaults;
use Aerni\AdvancedSeo\View\GraphQlCascade;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Type;
use Statamic\Support\Str;

class CascadeType extends Type
{
    const NAME = 'AdvancedSeoCascade';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        // Get all the blueprints
        $blueprints = Defaults::all()
            ->map(fn ($default) => $default['blueprint'])
            ->push(OnPageSeoBlueprint::class)
            ->unique();

         // Prepare the fields that should be available to the user
        return $blueprints->flatMap(fn ($blueprint) => app($blueprint)::make()->get()->fields()->toGql()) // Get all the fields
            ->filter(fn ($field, $handle) => ! Str::contains($handle, 'section_')) // We don't want to expose any section fields
            ->mapWithKeys(fn ($field, $handle) => [Str::remove('seo_', $handle) => $field]) // We want to remove `seo_` from all the field keys
            ->map(function ($field, $handle) {
                // if ($handle === 'og_image') {
                //     dd($field, $handle);
                // }
                // TODO: Have to set the type as well. The `og_image` is sometimes an asset, sometimes a social image. And the social image just augments to a string and not an asset.
                // So the type for this field has to be set dynamically.
                $field['resolve'] = $this->resolver(); // Tell each field how to get its value

                return $field;
            })->all();
    }

    private function resolver(): callable
    {
        return function (GraphQlCascade $cascade, $args, $context, ResolveInfo $info) {
            return $cascade->value($info->fieldName);
        };
    }
}
