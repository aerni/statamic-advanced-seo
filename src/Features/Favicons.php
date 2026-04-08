<?php

namespace Aerni\AdvancedSeo\Features;

class Favicons extends Feature
{
    protected static function available(): bool
    {
        return config('advanced-seo.favicons.enabled', true);
    }
}
