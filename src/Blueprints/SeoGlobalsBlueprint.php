<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Facades\Aerni\AdvancedSeo\Blueprints\Sections\FaviconsSection;
use Facades\Aerni\AdvancedSeo\Blueprints\Sections\SitemapSection;
use Facades\Aerni\AdvancedSeo\Blueprints\Sections\TrackersSection;
use Facades\Aerni\AdvancedSeo\Blueprints\Sections\GeneralSection;
use Facades\Aerni\AdvancedSeo\Blueprints\Sections\SocialSection;
use Aerni\AdvancedSeo\Contracts\Blueprint;

class SeoGlobalsBlueprint implements Blueprint
{
    public function contents(): array
    {
        return [
            'sections' => $this->sections(),
        ];
    }

    public function sections(): array
    {
        return array_filter([
            'general' => GeneralSection::contents(),
            'favicons' => FaviconsSection::contents(),
            'social' => SocialSection::contents(),
            'trackers' => TrackersSection::contents(),
            'sitemap' => SitemapSection::contents(),
        ]);
    }
}
