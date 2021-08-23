<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Repositories\CollectionDefaultsRepository;
use Statamic\Entries\Collection as StatamicCollection;
use Statamic\Facades\Collection;

class CollectionDefaultsController extends ContentDefaultsController
{
    protected function getContentItem(string $handle): StatamicCollection
    {
        return Collection::find($handle);
    }

    protected function getContentRepository(string $handle): CollectionDefaultsRepository
    {
        return new CollectionDefaultsRepository($handle);
    }
}
