<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\SeoSets\SeoSet;
use Statamic\Support\Traits\FluentlyGetsAndSets;

trait HasSeoSet
{
    use FluentlyGetsAndSets;

    protected string|SeoSet $seoSet;

    public function seoSet(string|SeoSet|null $seoSet = null): SeoSet|self
    {
        return $this->fluentlyGetOrSet('seoSet')
            ->getter(function ($seoSet) {
                return $seoSet instanceof SeoSet ? $seoSet : Seo::find($seoSet);
            })
            ->args(func_get_args());
    }
}
