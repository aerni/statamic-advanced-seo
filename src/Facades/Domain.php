<?php

namespace Aerni\AdvancedSeo\Facades;

use Aerni\AdvancedSeo\Registries\DomainRegistry;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection all()
 * @method static \Aerni\AdvancedSeo\Sitemaps\Domain|null find(string $name)
 * @method static \Aerni\AdvancedSeo\Sitemaps\Domain forSite(\Statamic\Sites\Site $site)
 *
 * @see DomainRegistry
 */
class Domain extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DomainRegistry::class;
    }
}
