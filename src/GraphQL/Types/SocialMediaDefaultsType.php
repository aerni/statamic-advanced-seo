<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Blueprints\SocialMediaBlueprint;
use Aerni\AdvancedSeo\Data\SeoSetLocalization;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Type;

class SocialMediaDefaultsType extends Type
{
    const NAME = 'socialMediaDefaults';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        return SocialMediaBlueprint::definition()->fields()->toGql()
            ->map(function ($field, $handle) {
                $field['resolve'] = $this->resolver();

                return $field;
            })->all();
    }

    private function resolver(): callable
    {
        return function (SeoSetLocalization $variables, $args, $context, ResolveInfo $info) {
            return $variables->resolveGqlValue($info->fieldName);
        };
    }
}
