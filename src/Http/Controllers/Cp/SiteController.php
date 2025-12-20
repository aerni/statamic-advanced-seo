<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

class SiteController extends BaseDefaultsController
{
    protected function type(): string
    {
        return 'site';
    }

    protected function icon(): string
    {
        return 'web';
    }
}
