<?php

namespace Aerni\AdvancedSeo\GraphQL\Resolvers;

use Aerni\AdvancedSeo\Data\SeoSetLocalization;

class SeoSetLocalizationResolver
{
    public static function resolve(string $field): callable
    {
        return fn (SeoSetLocalization $localization) => $localization->resolveGqlValue($field);
    }
}
