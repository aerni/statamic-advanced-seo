<?php

namespace Aerni\AdvancedSeo\Features;

use Statamic\Facades\Site;

class Hreflang extends Feature
{
    protected static function available(): bool
    {
        return Site::hasMultiple();
    }
}
