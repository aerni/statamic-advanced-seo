<?php

namespace Aerni\AdvancedSeo\View;

use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Contracts\View\View;
use Statamic\Facades\Cascade;
use Statamic\Tags\Context;

class CascadeComposer
{
    public function compose(View $view): void
    {
        /**
         * This prevents a "Serialization of 'Closure' is not allowed" exception when using the {{ nocache }} tag.
         * The issue is caused by closures in the config array. We don't need this value for the ViewCascade
         * so we can safely remove it here. See: https://github.com/aerni/statamic-advanced-seo/issues/175
         */
        $values = collect($view->getData())->except('config');

        $context = new Context($values);

        if (! $context->has('current_template')) {
            $context = $this->getContextFromCascade($context);
        }

        if (! $this->shouldProcessCascade($context)) {
            return;
        }

        $view->with('seo', ViewCascade::from($context));
    }

    protected function getContextFromCascade(Context $context): Context
    {
        $cascade = Cascade::instance();

        /**
         * If the cascade has not yet been hydrated, ensure it is hydrated.
         * This is important for people using custom route/controller/view implementations.
         */
        if (empty($cascade->toArray())) {
            $cascade->hydrate();
        }

        return $context->merge($cascade->toArray());
    }

    protected function shouldProcessCascade(Context $context): bool
    {
        // Don't process the cascade if it has been processed before.
        if ($context->has('seo')) {
            return false;
        }

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

        // Don't process the cascade for any custom route that doesn't explicitly want to use Advanced SEO.
        if (Helpers::isCustomRoute() && ! $context->bool('seo_enabled')) {
            return false;
        }

        return true;
    }
}
