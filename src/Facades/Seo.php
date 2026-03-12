<?php

namespace Aerni\AdvancedSeo\Facades;

use Aerni\AdvancedSeo\Registries\SeoSetRegistry;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection all()
 * @method static \Aerni\AdvancedSeo\Data\SeoSet|null find(string $id)
 * @method static \Illuminate\Support\Collection whereType(string $type)
 * @method static mixed defaultValue(string $key, mixed $default = null)
 *
 * @see SeoSetRegistry
 */
class Seo extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SeoSetRegistry::class;
    }
}
