<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\SeoSets\SeoSet;
use Aerni\AdvancedSeo\SeoSets\SeoSetGroup;
use Statamic\Http\Controllers\CP\CpController;

class SeoSetStateController extends CpController
{
    public function enable(SeoSetGroup $seoSetGroup, SeoSet $seoSet): void
    {
        $this->authorize('configure', [SeoSet::class, $seoSet]);

        $seoSet->config()->enabled(true)->save();
    }

    public function disable(SeoSetGroup $seoSetGroup, SeoSet $seoSet): void
    {
        $this->authorize('configure', [SeoSet::class, $seoSet]);

        $seoSet->config()->enabled(false)->data([])->save();
    }
}
