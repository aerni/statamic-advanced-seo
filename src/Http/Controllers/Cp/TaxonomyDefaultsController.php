<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Statamic\Facades\Taxonomy;
use Statamic\Taxonomies\Taxonomy as StatamicTaxonomy;
use Aerni\AdvancedSeo\Repositories\TaxonomyDefaultsRepository;
use Aerni\AdvancedSeo\Http\Controllers\Cp\ContentDefaultsController;

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
