<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Contracts\SeoSet;
use Illuminate\Support\Collection;
use Statamic\Facades\Site;

class GetAuthorizedSites
{
    public static function handle(SeoSet $default): Collection
    {
        return Site::authorized()->intersect($default->sites());
    }
}
