<?php

namespace Aerni\AdvancedSeo\Facades;

use Aerni\AdvancedSeo\Services\SeoService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection all()
 * @method static \Aerni\AdvancedSeo\SeoSets\SeoSet|null find(string $id)
 * @method static \Illuminate\Support\Collection whereType(string $type)
 * @method static \Illuminate\Support\Collection groups()
 * @method static mixed defaultValue(string $key, mixed $default = null)
 * @method static \Aerni\AdvancedSeo\SeoSets\SeoData data()
 *
 * @see SeoService
 */
class Seo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SeoService::class;
    }
}
