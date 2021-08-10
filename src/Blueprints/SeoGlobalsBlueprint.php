<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Fieldsets\FaviconsFieldset;
use Aerni\AdvancedSeo\Fieldsets\GeneralFieldset;
use Aerni\AdvancedSeo\Fieldsets\SitemapFieldset;
use Aerni\AdvancedSeo\Fieldsets\SocialFieldset;
use Aerni\AdvancedSeo\Fieldsets\TrackersFieldset;

class SeoGlobalsBlueprint extends BaseBlueprint
{
    protected array $sections = [
        'general' => GeneralFieldset::class,
        'favicons' => FaviconsFieldset::class,
        'social' => SocialFieldset::class,
        'trackers' => TrackersFieldset::class,
        'sitemap' => SitemapFieldset::class,
    ];
}
