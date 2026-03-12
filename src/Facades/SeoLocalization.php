<?php

namespace Aerni\AdvancedSeo\Facades;

use Aerni\AdvancedSeo\Contracts\SeoSetLocalizationRepository;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Aerni\AdvancedSeo\Contracts\SeoSetLocalization make()
 * @method static \Aerni\AdvancedSeo\Contracts\SeoSetLocalization|null find(string $id)
 * @method static \Illuminate\Support\Collection all()
 * @method static \Illuminate\Support\Collection whereSeoSet(string $id)
 * @method static void save(\Aerni\AdvancedSeo\Contracts\SeoSetLocalization $localization)
 * @method static void delete(\Aerni\AdvancedSeo\Contracts\SeoSetLocalization $localization)
 *
 * @see SeoSetLocalizationRepository
 * @see \Aerni\AdvancedSeo\Stache\Repositories\SeoSetLocalizationRepository
 */
class SeoLocalization extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SeoSetLocalizationRepository::class;
    }
}
