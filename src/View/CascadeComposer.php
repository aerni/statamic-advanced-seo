<?php

namespace Aerni\AdvancedSeo\View;

use Statamic\Tags\Context;
use Statamic\Facades\Cascade;
use Illuminate\Contracts\View\View;
use Aerni\AdvancedSeo\Support\Helpers;

class CascadeComposer
{
    public function compose(View $view): void
    {
        $context = new Context($view->getData());

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
