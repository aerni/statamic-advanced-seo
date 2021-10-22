<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Repositories\SeoDefaultsRepository;
use Statamic\Facades\Taxonomy;

class TaxonomyDefaultsController extends ContentDefaultsController
{
    protected string $type = 'taxonomy';

    protected function repository(string $handle): SeoDefaultsRepository
    {
        return new SeoDefaultsRepository('taxonomies', $handle, $this->content($handle)->sites());
    }

    protected function content(string $handle): mixed
    {
        return Taxonomy::find($handle);
    }
}
