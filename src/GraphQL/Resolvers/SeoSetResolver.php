<?php

namespace Aerni\AdvancedSeo\GraphQL\Resolvers;

use Aerni\AdvancedSeo\SeoSets\SeoSetLocalization;
use Aerni\AdvancedSeo\Facades\Seo;

class SeoSetResolver
{
    public static function resolve(string $id, ?string $site = null): ?SeoSetLocalization
    {
        if (! $set = Seo::find($id)) {
            return null;
        }

        if (! $set->enabled()) {
            return null;
        }

        return $site
            ? $set->in($site)
            : $set->inDefaultSite();
    }
}
