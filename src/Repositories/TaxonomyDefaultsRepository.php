<?php

namespace Aerni\AdvancedSeo\Repositories;

use Statamic\Fields\Blueprint;
use Aerni\AdvancedSeo\Blueprints\ContentDefaultsBlueprint;
use Aerni\AdvancedSeo\Repositories\BaseDefaultsRepository;

class TaxonomyDefaultsRepository extends BaseDefaultsRepository
{
    public string $contentType = 'taxonomies';

    public function blueprint(): Blueprint
    {
        return ContentDefaultsBlueprint::make()->get();
    }
}
