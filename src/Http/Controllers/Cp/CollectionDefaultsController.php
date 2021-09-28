<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Statamic\Facades\Collection;
use Aerni\AdvancedSeo\Repositories\CollectionDefaultsRepository;

class CollectionDefaultsController extends ContentDefaultsController
{
    protected string $type = 'collection';

    protected function repository(string $handle): CollectionDefaultsRepository
    {
        return new CollectionDefaultsRepository($handle);
    }

    protected function content(string $handle): mixed
    {
        return Collection::find($handle);
    }
}
