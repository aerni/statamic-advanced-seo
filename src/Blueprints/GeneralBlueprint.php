<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Fields\GeneralFields;
use Aerni\AdvancedSeo\Fields\FaviconsFields;
use Aerni\AdvancedSeo\Fields\SitemapFields;
use Aerni\AdvancedSeo\Fields\SocialFields;
use Aerni\AdvancedSeo\Fields\TrackersFields;

class GeneralBlueprint extends BaseBlueprint
{
    protected function sections(): array
    {
        return [
            'general' => GeneralFields::class,
            'favicons' => FaviconsFields::class,
            'social' => SocialFields::class,
            'trackers' => TrackersFields::class,
            'sitemap' => SitemapFields::class,
        ];
    }
}
