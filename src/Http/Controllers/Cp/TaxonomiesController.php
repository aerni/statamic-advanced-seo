<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

class TaxonomiesController extends BaseSeoSetLocalizationController
{
    protected function type(): string
    {
        return 'taxonomies';
    }

    protected function icon(): string
    {
        return 'taxonomies';
    }
}
