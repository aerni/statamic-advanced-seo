<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Blueprints\ContentSeoSetLocalizationBlueprint;
use Aerni\AdvancedSeo\Data\SeoSetLocalization;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Type;
use Statamic\Support\Str;

class ContentDefaultsType extends Type
{
    const NAME = 'contentDefaults';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The Advanced SEO collection and taxonomy defaults',
    ];

    public function fields(): array
    {
        return ContentSeoSetLocalizationBlueprint::make()->get()->fields()->toGql()
            ->mapWithKeys(fn ($field, $handle) => [Str::remove('seo_', $handle) => $field]) // We want to remove `seo_` from all the field keys
            ->map(function ($field, $handle) {
                $field['resolve'] = $this->resolver();

                return $field;
            })->all();
    }

    private function resolver(): callable
    {
        return function (SeoSetLocalization $variables, $args, $context, ResolveInfo $info) {
            return $variables->resolveGqlValue("seo_{$info->fieldName}");
        };
    }
}
