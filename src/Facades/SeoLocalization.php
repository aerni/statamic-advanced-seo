<?php

namespace Aerni\AdvancedSeo\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Aerni\AdvancedSeo\Contracts\SeoSetLocalization make(string $seoSet, string $locale)
 * @method static \Illuminate\Support\Collection all()
 * @method static \Aerni\AdvancedSeo\Contracts\SeoSetLocalization|null find(string $id)
 * @method static \Illuminate\Support\Collection whereSeoSet(string $type, string $handle)
 * @method static void save(\Aerni\AdvancedSeo\Contracts\SeoSetLocalization $localization)
 * @method static void delete(\Aerni\AdvancedSeo\Contracts\SeoSetLocalization $localization)
 *
 * @see \Aerni\AdvancedSeo\Contracts\SeoSetLocalizationRepository
 */
class SeoLocalization extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Aerni\AdvancedSeo\Contracts\SeoSetLocalizationRepository::class;
    }
}
