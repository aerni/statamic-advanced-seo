<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Contracts\SeoDefaultSet;
use Illuminate\Support\Collection;
use Statamic\Facades\Site;

class GetAuthorizedSites
{
    public static function handle(SeoDefaultSet $set): Collection
    {
        return Site::authorized()->intersect($set->sites());
    }
}
