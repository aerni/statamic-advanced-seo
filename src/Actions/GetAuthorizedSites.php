<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Features\Hreflang;
use Aerni\AdvancedSeo\Features\MultiSite;
use Aerni\AdvancedSeo\SeoSets\SeoSet;
use Illuminate\Support\Collection;
use Statamic\Facades\Site;

class GetAuthorizedSites
{
    public static function handle(SeoSet $set): Collection
    {
        if (! MultiSite::enabled() && ! Hreflang::enabled()) {
            return collect([Site::default()]);
        }

        return Site::authorized()->intersect($set->sites());
    }
}
