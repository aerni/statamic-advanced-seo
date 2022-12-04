<?php

namespace Aerni\AdvancedSeo\Tags;

use Aerni\AdvancedSeo\Support\Helpers;
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
        // Don't add data for collections that are excluded in the config.
        if ($this->context->has('is_entry') && in_array($this->context->get('collection')->raw()->handle(), config('advanced-seo.disabled.collections', []))) {
            return false;
        }

        // Don't add data for taxonomy terms that are excluded in the config.
        if ($this->context->has('is_term') && in_array($this->context->get('taxonomy')->raw()->handle(), config('advanced-seo.disabled.taxonomies', []))) {
            return false;
        }

        // Don't add data for taxonomies that are excluded in the config.
        if ($this->context->has('terms') && in_array($this->context->get('handle')->raw(), config('advanced-seo.disabled.taxonomies', []))) {
            return false;
        }

        // Custom routes don't have the necessary data to compose the SEO cascade.
        if (Helpers::isCustomRoute()) {
            return false;
        }

        return true;
    }

    /**
     * Only render the analytics tags in the environments defined in the config.
     */
    public function shouldRenderAnalyticsTags(): bool
    {
        return in_array(app()->environment(), config('advanced-seo.analytics.environments', ['production']));
    }
}
