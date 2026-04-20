<?php

namespace Aerni\AdvancedSeo\GraphQL\Resolvers;

use Aerni\AdvancedSeo\SeoSets\SeoSetLocalization;
use GraphQL\Type\Definition\ResolveInfo;

class SeoSetLocalizationResolver
{
    /**
     * Wraps field resolvers so GraphQL field names resolve against the correct blueprint handle.
     * This is necessary when GraphQL exposes unprefixed names (e.g. `sitemap_enabled`)
     * but the blueprint uses prefixed handles (e.g. `seo_sitemap_enabled`).
     */
    public static function resolve(array $field, string $handle): callable
    {
        $resolver = $field['resolve'] ?? null;

        return static function (SeoSetLocalization $localization, array $args, mixed $context, ResolveInfo $info) use ($resolver, $handle) {
            if (! $resolver) {
                return $localization->resolveGqlValue($handle);
            }

            $info->fieldName = $handle;

            return $resolver($localization, $args, $context, $info);
        };
    }
}
