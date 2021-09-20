<?php

namespace Aerni\AdvancedSeo\Repositories;

use Statamic\Fields\Blueprint;
use Aerni\AdvancedSeo\Blueprints\ContentDefaultsBlueprint;
use Aerni\AdvancedSeo\Repositories\BaseDefaultsRepository;

class CollectionDefaultsRepository extends BaseDefaultsRepository
{
    public string $contentType = 'collections';

    public function blueprint(): Blueprint
    {
        return ContentDefaultsBlueprint::make()->get();
    }
}
