<?php

namespace Aerni\AdvancedSeo\Tags;

use Statamic\Tags\Tags;
use Illuminate\Support\Arr;
use Aerni\AdvancedSeo\View\Cascade;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AdvancedSeoTags extends Tags
{
    protected static $handle = 'advanced_seo';

    /**
     * Gets a specific key from the seo cascade by wildcard method.
     * This lets you access seo data from anywhere, e.g. {{ advanced_seo:site_name }}
     */
    public function wildcard(): mixed
    {
        return Arr::get($this->cascade()->get('seo'), $this->method);
    }

    /**
     * Renders the head view with the seo cascade.
     */
    public function head(): View
    {
        return view('advanced-seo::head', $this->cascade());
    }

    /**
     * Renders the body view with the seo cascade.
     */
    public function body(): View
    {
        return view('advanced-seo::body', $this->cascade());
    }

    /**
     * Builds the seo cascade from the context.
     */
    protected function cascade(): Collection
    {
        return Cascade::make($this->context)->get();
    }
}
