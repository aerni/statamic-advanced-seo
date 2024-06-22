<?php

namespace Aerni\AdvancedSeo\View;

use Statamic\Tags\Context;
use Statamic\Facades\Cascade;
use Illuminate\Contracts\View\View;
use Aerni\AdvancedSeo\Actions\ShouldProcessViewCascade;

class CascadeComposer
{
    public function compose(View $view): void
    {
        $context = new Context($view->getData());

        if (! $context->has('current_template')) {
            $context = $this->getContextFromCascade($context);
        }

        if (! ShouldProcessViewCascade::handle($context)) {
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
}
