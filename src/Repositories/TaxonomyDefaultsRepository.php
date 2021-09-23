<?php

namespace Aerni\AdvancedSeo\Repositories;

use Aerni\AdvancedSeo\Blueprints\ContentDefaultsBlueprint;
use Statamic\Fields\Blueprint;

class TaxonomyDefaultsRepository extends BaseDefaultsRepository
{
    public string $contentType = 'taxonomies';

    public function blueprint(): Blueprint
    {
        return ContentDefaultsBlueprint::make()->get();
    }
}
