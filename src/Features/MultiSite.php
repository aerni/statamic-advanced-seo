<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\AdvancedSeo;
use Aerni\AdvancedSeo\SeoSets\SeoSet;
use Statamic\Facades\Site;

class MultiSite extends Feature
{
    protected static function available(): bool
    {
        return AdvancedSeo::pro() && Site::hasMultiple();
    }

    protected static function enabledInConfig(SeoSet $set): bool
    {
        return $set->sites()->count() > 1;
    }
}
