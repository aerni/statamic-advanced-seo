<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Repositories\TaxonomyDefaultsRepository;
use Statamic\Facades\Taxonomy;

class TaxonomyDefaultsController extends ContentDefaultsController
{
    protected string $type = 'taxonomy';

    protected function repository(string $handle): TaxonomyDefaultsRepository
    {
        return new TaxonomyDefaultsRepository($handle);
    }

    protected function content(string $handle): mixed
    {
        return Taxonomy::find($handle);
    }
}
