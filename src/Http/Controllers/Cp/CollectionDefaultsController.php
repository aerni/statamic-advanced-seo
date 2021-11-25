<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Facades\Collection;

class CollectionDefaultsController extends ContentDefaultsController
{
    protected string $type = 'collections';

    protected function set(string $handle): SeoDefaultSet
    {
        return Seo::findOrMake('collections', $handle);
    }

    protected function content(string $handle): mixed
    {
        return Collection::find($handle);
    }
}
