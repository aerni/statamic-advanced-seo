<?php

namespace Aerni\AdvancedSeo\Tags;

use Aerni\AdvancedSeo\View\Cascade;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Statamic\Facades\Blink;
use Statamic\Tags\Tags;

class AdvancedSeoTags extends Tags
{
    protected static $handle = 'seo';

    /**
     * Returns a specific variable from the seo cascade.
     */
    public function wildcard(): mixed
    {
        return Arr::get($this->cascade(), $this->method);
    }

    /**
     * Renders the head view with the seo cascade.
     */
    public function head(): ?View
    {
        if (! $this->seoIsEnabled()) {
            return null;
        }

        return view('advanced-seo::head', $this->cascade());
    }

    /**
     * Renders the body view with the seo cascade.
     */
    public function body(): ?View
    {
        if (! $this->seoIsEnabled()) {
            return null;
        }

        return view('advanced-seo::body', $this->cascade());
    }

    /**
     * Dumps the seo cascade for easier debugging.
     */
    public function dump(): void
    {
        dd($this->cascade());
    }

    /**
     * Returns the computed seo cascade.
     */
    protected function cascade(): array
    {
        return Blink::once('advanced-seo::cascade', function () {
            return Cascade::from($this->context)
                ->withSiteDefaults()
                ->withPageData()
                ->processForFrontend()
                ->get();
        });
    }

    /**
     * Check if we should render any meta data.
     */
    protected function seoIsEnabled(): bool
    {
        // Don't add data for collections that are excluded in the config.
        if ($this->context->has('is_entry') && in_array($this->context->get('collection')->handle(), config('advanced-seo.disabled.collections', []))) {
            return false;
        }

        // Don't add data for taxonomy terms that are excluded in the config.
        if ($this->context->has('is_term') && in_array($this->context->get('taxonomy')->handle(), config('advanced-seo.disabled.taxonomies', []))) {
            return false;
        }

        // Don't add data for taxonomies that are excluded in the config.
        if ($this->context->has('terms') && in_array($this->context->get('handle'), config('advanced-seo.disabled.taxonomies', []))) {
            return false;
        }

        return true;
    }
}
