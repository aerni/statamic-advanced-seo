<?php

namespace Aerni\AdvancedSeo\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Aerni\AdvancedSeo\Contracts\SeoSetConfig make(string $type, string $handle)
 * @method static \Aerni\AdvancedSeo\Contracts\SeoSetConfig|null find(string $type, string $handle)
 * @method static \Aerni\AdvancedSeo\Contracts\SeoSetConfig findOrMake(string $type, string $handle)
 * @method static \Illuminate\Support\Collection all()
 * @method static \Statamic\Data\DataCollection whereType(string $type)
 * @method static \Aerni\AdvancedSeo\Contracts\SeoSetConfigRepository save(\Aerni\AdvancedSeo\Contracts\SeoSetConfig $config)
 * @method static bool delete(\Aerni\AdvancedSeo\Contracts\SeoSetConfig $config)
 *
 * @see \Aerni\AdvancedSeo\Contracts\SeoSetConfigRepository
 */
class SeoConfig extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Aerni\AdvancedSeo\Contracts\SeoSetConfigRepository::class;
    }
}
