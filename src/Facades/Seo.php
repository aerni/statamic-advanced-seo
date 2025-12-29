<?php

namespace Aerni\AdvancedSeo\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection all()
 * @method static \Aerni\AdvancedSeo\Contracts\SeoSet|null find(string $id)
 * @method static \Illuminate\Support\Collection whereType(string $type)
 *
 * @see \Aerni\AdvancedSeo\Registries\SeoSetRegistry
 */
class Seo extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Aerni\AdvancedSeo\Registries\SeoSetRegistry::class;
    }
}
