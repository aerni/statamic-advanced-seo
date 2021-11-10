<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Statamic\Facades\Taxonomy;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Data\SeoDefaultSet;

class TaxonomyDefaultsController extends ContentDefaultsController
{
    protected string $type = 'taxonomy';

    protected function set(string $handle): SeoDefaultSet
    {
        return Seo::findOrMake('taxonomies', $handle);
    }

    protected function content(string $handle): mixed
    {
        return Taxonomy::find($handle);
    }
}
