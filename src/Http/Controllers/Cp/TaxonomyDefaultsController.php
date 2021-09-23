<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Repositories\TaxonomyDefaultsRepository;

class TaxonomyDefaultsController extends ContentDefaultsController
{
    protected string $type = 'taxonomy';

    protected function repository(string $handle): TaxonomyDefaultsRepository
    {
        return new TaxonomyDefaultsRepository($handle);
    }
}
