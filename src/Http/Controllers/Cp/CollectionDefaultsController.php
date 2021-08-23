<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Statamic\Facades\Collection;
use Statamic\Entries\Collection as StatamicCollection;
use Aerni\AdvancedSeo\Repositories\CollectionDefaultsRepository;
use Aerni\AdvancedSeo\Http\Controllers\Cp\ContentDefaultsController;

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
