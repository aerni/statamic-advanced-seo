<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Repositories\SeoDefaultsRepository;
use Statamic\Facades\Collection;

class CollectionDefaultsController extends ContentDefaultsController
{
    protected string $type = 'collection';

    protected function repository(string $handle): SeoDefaultsRepository
    {
        return new SeoDefaultsRepository('collections', $handle, $this->content($handle)->sites());
    }

    protected function content(string $handle): mixed
    {
        return Collection::find($handle);
    }
}
