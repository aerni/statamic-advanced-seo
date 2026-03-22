<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\SeoSets\SeoSet;
use Illuminate\Support\Collection;
use Statamic\Facades\Site;

class GetAuthorizedSites
{
    public static function handle(SeoSet $set): Collection
    {
        return Site::authorized()->intersect($set->sites());
    }
}
