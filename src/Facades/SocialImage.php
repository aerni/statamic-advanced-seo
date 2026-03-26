<?php

namespace Aerni\AdvancedSeo\Facades;

use Aerni\AdvancedSeo\Registries\SocialImageThemeRegistry;
use Aerni\AdvancedSeo\SocialImages\SocialImageService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Aerni\AdvancedSeo\SocialImages\SocialImage|null find(string $type)
 * @method static \Aerni\AdvancedSeo\SocialImages\SocialImage openGraph()
 * @method static Collection for(\Statamic\Contracts\Entries\Entry|\Statamic\Contracts\Taxonomies\Term $content)
 * @method static Collection all()
 * @method static SocialImageThemeRegistry themes()
 *
 * @see SocialImageService
 */
class SocialImage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SocialImageService::class;
    }
}
