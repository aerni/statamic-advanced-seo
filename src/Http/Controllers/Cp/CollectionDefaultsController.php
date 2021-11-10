<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Statamic\Facades\Collection;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Data\SeoDefaultSet;

class CollectionDefaultsController extends ContentDefaultsController
{
    protected string $type = 'collection';

    protected function set(string $handle): SeoDefaultSet
    {
        return Seo::findOrMake('collections', $handle);
    }

    protected function content(string $handle): mixed
    {
        return Collection::find($handle);
    }
}
