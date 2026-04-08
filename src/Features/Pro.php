<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\AdvancedSeo;

class Pro extends Feature
{
    protected static function available(): bool
    {
        return AdvancedSeo::pro();
    }
}
