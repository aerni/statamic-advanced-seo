<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\AdvancedSeo;
use Statamic\Facades\Site;

class MultiSite extends Feature
{
    protected static function available(): bool
    {
        return AdvancedSeo::pro() && Site::hasMultiple();
    }
}
