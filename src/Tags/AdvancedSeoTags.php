<?php

namespace Aerni\AdvancedSeo\Tags;

use Aerni\AdvancedSeo\Actions\ShouldProcessViewCascade;
use Illuminate\View\View;
use Statamic\Tags\Tags;

class AdvancedSeoTags extends Tags
{
    protected static $handle = 'seo';

    /**
     * Make sure to not throw an exception if a {{ seo:variable }} doesn't exist.
     * Example: A user wants to return a computed variable `hreflang` which doesn't exist on a particular page.
     * Without returning `null` here, he would get an exception because Statamic is now expecting
     * a `hreflang` method on this class, which we obviously don't have.
     */
    public function wildcard()
    {
        return null;
    }

    /**
     * Renders the head view.
     */
    public function head(): ?View
    {
        if (! $this->shouldRenderView()) {
            return null;
        }

        return view('advanced-seo::head', $this->context->all());
    }

    /**
     * Renders the body view.
     */
    public function body(): ?View
    {
        if (! $this->shouldRenderView()) {
            return null;
        }

        return view('advanced-seo::body', $this->context->all());
    }

    /**
     * Dumps the seo variables for easier debugging.
     */
    public function dump(): void
    {
        dd($this->context->get('seo'));
    }

    /**
     * Determines if we should render the Advanced SEO views.
     */
    protected function shouldRenderView(): bool
    {
        return ShouldProcessViewCascade::handle($this->context);
    }

    /**
     * Only render the analytics tags in the environments defined in the config.
     */
    public function shouldRenderAnalyticsTags(): bool
    {
        return in_array(app()->environment(), config('advanced-seo.analytics.environments', ['production']));
    }
}
