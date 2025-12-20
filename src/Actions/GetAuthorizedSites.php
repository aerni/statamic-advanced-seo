<?php

namespace Aerni\AdvancedSeo\Actions;

use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Contracts\SeoDefaultSet;
use Statamic\Facades\Site;

class GetAuthorizedSites
{
    public static function handle(SeoDefaultSet $set): Collection
    {
        return Site::authorized()->intersect($set->sites());
    }
}
