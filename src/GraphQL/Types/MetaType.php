<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Aerni\AdvancedSeo\Models\Defaults;
use Aerni\AdvancedSeo\View\GraphQlCascade;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;
use Statamic\Support\Str;

class MetaType extends Type
{
    const NAME = 'AdvancedSeoMeta';

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
        $fields = $blueprints->flatMap(fn ($blueprint) => app($blueprint)::make()->get()->fields()->toGql()) // Get all the fields
            ->mapWithKeys(fn ($field, $handle) => [Str::remove('seo_', $handle) => $field]) // We want to remove `seo_` from all the field keys
            ->only(GraphQlCascade::whitelist()); // Only keep necessary fields.

        $computedFields = [
            'locale' => [
                'type' => GraphQl::string(),
            ],
            'indexing' => [
                'type' => GraphQl::string(),
            ],
            'canonical' => [
                'type' => GraphQl::string(),
            ],
            'hreflang' => [
                'type' => GraphQl::listOf(GraphQL::type(HreflangType::NAME)),
            ],
            'breadcrumbs' => [
                'type' => GraphQl::string(),
            ],
            'schema' => [
                'type' => GraphQl::string(),
            ],
        ];

        return $fields->merge($computedFields)
            ->map(function ($field, $handle) {
                $field['resolve'] = $this->resolver(); // Tell each field how to get its value

                return $field;
            })->all();

        /**
         * TODO: Have to set the type as well. The `og_image` is sometimes an asset, sometimes a social image.
         * And the social image just augments to a string and not an asset.
         * We shouldn't merge different fields into the same key. Each field has to stay the way it was.
         */

        /**
         * TODO: There are a bunch of computed fields like hreflang or breadcrumbs.
         * These have to be included too.
         * Because they don't have a blueprint field, I have to manually add them with type etc.
         */
    }

    private function resolver(): callable
    {
        return function (GraphQlCascade $cascade, $args, $context, ResolveInfo $info) {
            return $cascade->value($info->fieldName);
        };
    }
}
