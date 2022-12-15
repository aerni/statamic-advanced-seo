<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Support\Helpers;
use Statamic\Tags\Context;

class ShouldProcessViewCascade
{
    public static function handle(Context $context): bool
    {
        // Don't process the cascade for collections that are excluded in the config.
        if ($context->has('is_entry') && in_array($context->get('collection')->raw()->handle(), config('advanced-seo.disabled.collections', []))) {
            return false;
        }

        // Don't process the cascade for taxonomy terms that are excluded in the config.
        if ($context->has('is_term') && in_array($context->get('taxonomy')->raw()->handle(), config('advanced-seo.disabled.taxonomies', []))) {
            return false;
        }

        // Don't process the cascade for taxonomies that are excluded in the config.
        if ($context->has('terms') && in_array($context->get('handle')->raw(), config('advanced-seo.disabled.taxonomies', []))) {
            return false;
        }

        // Don't process the cascade for custom routes, as they don't have the necessary data to compose the SEO cascade.
        if (Helpers::isCustomRoute()) {
            return false;
        }

        return true;
    }
}
