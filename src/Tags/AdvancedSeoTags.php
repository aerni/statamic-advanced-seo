<?php

namespace Aerni\AdvancedSeo\Tags;

use Statamic\Tags\Tags;
use Illuminate\View\View;

class AdvancedSeoTags extends Tags
{
    protected static $handle = 'seo';

    /**
     * This method simply returns an seo value by key. It prevents edge case errors
     * where the {{ seo }} tag takes precedence over the {{ seo }} variable in the context.
     */
    public function wildcard(string $key): mixed
    {
        return $this->context->get("seo.$key");
    }

    /**
     * Renders the head view with the seo cascade.
     */
    public function head(): View
    {
        return view('advanced-seo::head', $this->context->toArray());
    }

    /**
     * Renders the body view with the seo cascade.
     */
    public function body(): View
    {
        return view('advanced-seo::body', $this->context->toArray());
    }
}
