<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Statamic\Support\Str;
use Statamic\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;
use Aerni\AdvancedSeo\Models\Defaults;
use GraphQL\Type\Definition\ResolveInfo;
use Aerni\AdvancedSeo\View\GraphQlCascade;
use Statamic\GraphQL\Types\AssetInterface;
use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Aerni\AdvancedSeo\GraphQL\Types\SocialImagePresetType;

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

        // Define all the additional fields that are not part of the blueprint
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
            'twitter_image' => [
                'type' => GraphQl::type(AssetInterface::NAME),
            ],
            'og_image_preset' => [
                'type' => GraphQl::type(SocialImagePresetType::NAME),
            ],
            'twitter_image_preset' => [
                'type' => GraphQl::type(SocialImagePresetType::NAME),
            ],
        ];

        return $fields->merge($computedFields)->map(function ($field, $handle) {
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
