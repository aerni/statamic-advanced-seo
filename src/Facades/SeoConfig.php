<?php

namespace Aerni\AdvancedSeo\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Aerni\AdvancedSeo\Contracts\SeoSetConfig make()
 * @method static \Aerni\AdvancedSeo\Contracts\SeoSetConfig|null find(string $id)
 * @method static \Aerni\AdvancedSeo\Contracts\SeoSetConfig findOrMake(string $id)
 * @method static \Illuminate\Support\Collection all()
 * @method static void save(\Aerni\AdvancedSeo\Contracts\SeoSetConfig $config)
 * @method static bool delete(\Aerni\AdvancedSeo\Contracts\SeoSetConfig $config)
 *
 * @see \Aerni\AdvancedSeo\Contracts\SeoSetConfigRepository
 * @see \Aerni\AdvancedSeo\Stache\Repositories\SeoSetConfigRepository
 */
class SeoConfig extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Aerni\AdvancedSeo\Contracts\SeoSetConfigRepository::class;
    }
}
