<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Repositories\TaxonomyDefaultsRepository;
use Statamic\Facades\Taxonomy;
use Statamic\Taxonomies\Taxonomy as StatamicTaxonomy;

class TaxonomyDefaultsController extends ContentDefaultsController
{
    protected function getContentItem(string $handle): StatamicTaxonomy
    {
        return Taxonomy::find($handle);
    }

    protected function getContentRepository(string $handle): TaxonomyDefaultsRepository
    {
        return new TaxonomyDefaultsRepository($handle);
    }
}
