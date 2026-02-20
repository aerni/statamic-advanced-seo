<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Blueprints\SiteDefaultsBlueprint;
use Aerni\AdvancedSeo\GraphQL\Resolvers\SeoSetLocalizationResolver;
use Rebing\GraphQL\Support\Type;

class SiteSetType extends Type
{
    const NAME = 'siteSet';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The SEO set for the site',
    ];

    public function fields(): array
    {
        return SiteDefaultsBlueprint::definition()->fields()->toGql()
            ->map(fn ($field, $handle) => [...$field, 'resolve' => SeoSetLocalizationResolver::resolve($field, $handle)])
            ->all();
    }
}
