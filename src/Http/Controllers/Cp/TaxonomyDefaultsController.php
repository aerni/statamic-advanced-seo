<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Facades\Taxonomy;

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
