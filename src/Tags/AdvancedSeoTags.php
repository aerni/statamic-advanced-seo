<?php

namespace Aerni\AdvancedSeo\Tags;

use Illuminate\View\View;
use Statamic\Tags\Tags;

class AdvancedSeoTags extends Tags
{
    protected static $handle = 'seo';

    /**
     * The {{ seo }} variable takes precedence over the {{ seo }} tag.
     * If the requested variable key, e.g. {{ seo:hreflang }} returns nothing, this wildcard method will be run instead.
     * We simply want to return nothing as well.
     */
    public function wildcard(): mixed
    {
        return null;
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
