<?php

namespace Aerni\AdvancedSeo\Features;

class Fathom extends Feature
{
    protected static function available(): bool
    {
        return config('advanced-seo.analytics.fathom', true);
    }
}
