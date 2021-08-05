<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Contracts\Blueprint;
use Facades\Aerni\AdvancedSeo\Fieldsets\GeneralFieldset;
use Facades\Aerni\AdvancedSeo\Fieldsets\TrackersFieldset;
use Facades\Aerni\AdvancedSeo\Blueprints\Sections\FaviconsSection;
use Facades\Aerni\AdvancedSeo\Blueprints\Sections\GeneralSection;
use Facades\Aerni\AdvancedSeo\Blueprints\Sections\SitemapSection;
use Facades\Aerni\AdvancedSeo\Blueprints\Sections\SocialSection;
use Facades\Aerni\AdvancedSeo\Blueprints\Sections\TrackersSection;
use Statamic\Facades\Blueprint as FacadesBlueprint;

class SeoGlobalsBlueprint implements Blueprint
{
    public function contents(): array
    {
        return FacadesBlueprint::makeFromSections($this->sections())->contents();
    }

    public function sections(): array
    {
        return array_filter([
            'general' => GeneralFieldset::contents(),
            // 'favicons' => FaviconsSection::contents(),
            // 'social' => SocialSection::contents(),
            'trackers' => TrackersFieldset::contents(),
            // 'sitemap' => SitemapSection::contents(),
        ]);
    }
}
