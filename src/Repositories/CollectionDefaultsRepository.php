<?php

namespace Aerni\AdvancedSeo\Repositories;

use Aerni\AdvancedSeo\Blueprints\ContentDefaultsBlueprint;
use Statamic\Fields\Blueprint;

class CollectionDefaultsRepository extends BaseDefaultsRepository
{
    public string $contentType = 'collections';

    public function blueprint(): Blueprint
    {
        return ContentDefaultsBlueprint::make()->get();
    }
}
