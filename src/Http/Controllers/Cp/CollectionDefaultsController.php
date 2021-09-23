<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Repositories\CollectionDefaultsRepository;

class CollectionDefaultsController extends ContentDefaultsController
{
    protected string $type = 'collection';

    protected function repository(string $handle): CollectionDefaultsRepository
    {
        return new CollectionDefaultsRepository($handle);
    }
}
