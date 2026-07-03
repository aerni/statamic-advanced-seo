<?php

namespace Aerni\AdvancedSeo\Facades;

use Aerni\AdvancedSeo\Redirects\RedirectService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Aerni\AdvancedSeo\Contracts\Redirect make()
 * @method static \Aerni\AdvancedSeo\Contracts\Redirect|null find(string $id)
 * @method static \Illuminate\Support\Collection all()
 * @method static \Aerni\AdvancedSeo\Contracts\RedirectQueryBuilder query()
 * @method static void save(\Aerni\AdvancedSeo\Contracts\Redirect $redirect)
 * @method static void delete(\Aerni\AdvancedSeo\Contracts\Redirect $redirect)
 * @method static \Aerni\AdvancedSeo\Contracts\RedirectHitRepository hits()
 * @method static \Aerni\AdvancedSeo\Contracts\RedirectErrorRepository errors()
 * @method static \Aerni\AdvancedSeo\Redirects\ResolvedRedirect|null resolve(string $path, string $site)
 *
 * @see RedirectService
 */
class Redirect extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RedirectService::class;
    }
}
