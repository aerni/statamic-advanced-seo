<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\AdvancedSeo;
use Aerni\AdvancedSeo\Context\Context;

class GraphQL extends Feature
{
    public static function enabled(?Context $context = null): bool
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
