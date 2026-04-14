<?php

namespace Aerni\AdvancedSeo\Cascades;

use Aerni\AdvancedSeo\AdvancedSeo;
use Aerni\AdvancedSeo\Concerns\EvaluatesContextType;
use Aerni\AdvancedSeo\Context\Context as SeoContext;
use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Contracts\View\View;
use Statamic\Facades\Cascade;
use Statamic\Statamic;
use Statamic\Tags\Context;

class CascadeComposer
{
    use EvaluatesContextType;

    public function compose(View $view): void
    {
        /**
         * This prevents a "Serialization of 'Closure' is not allowed" exception when using the {{ nocache }} tag.
         * The issue is caused by closures in the config array. We don't need this value for the cascades.
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

        $cascade = $this->contextIsEntryOrTerm($context)
            ? ContentViewCascade::from($context->get('id')->augmentable())
            : ContextViewCascade::from($context);

        $view->with('seo', $cascade);
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

    protected function shouldProcessCascade(Context $viewContext): bool
    {
        if (Statamic::isCpRoute()) {
            return false;
        }

        // Don't reprocess if the cascade has already been computed on this view.
        if ($viewContext->has('seo')) {
            return false;
        }

        // Custom routes are a Pro feature requiring explicit opt-in.
        if (Helpers::isCustomRoute()) {
            return AdvancedSeo::pro() && $viewContext->bool('seo_enabled');
        }

        // Process when the SeoSet is enabled. Default-pass when no content
        // context can be resolved (e.g. partials or custom templates).
        return SeoContext::from($viewContext)?->seoSet()?->enabled() ?? true;
    }
}
