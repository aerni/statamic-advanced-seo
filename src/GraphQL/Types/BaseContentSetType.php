<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Blueprints\ContentSeoSetLocalizationBlueprint;
use Aerni\AdvancedSeo\GraphQL\Resolvers\SeoSetLocalizationResolver;
use Rebing\GraphQL\Support\Type;
use Statamic\Support\Str;

abstract class BaseContentSetType extends Type
{
    public function fields(): array
    {
        return ContentSeoSetLocalizationBlueprint::definition()->fields()->toGql()
            ->map(fn ($field, $handle) => [...$field, 'resolve' => SeoSetLocalizationResolver::resolve($field, $handle)])
            ->mapWithKeys(fn ($field, $handle) => [Str::remove('seo_', $handle) => $field])
            ->all();
    }
}
