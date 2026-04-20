<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\AdvancedSeo;

class GraphQL extends Feature
{
    protected static function available(): bool
    {
        if (! AdvancedSeo::pro()) {
            return false;
        }

        if (! config('statamic.graphql.enabled')) {
            return false;
        }

        return config('advanced-seo.graphql', false);
    }
}
