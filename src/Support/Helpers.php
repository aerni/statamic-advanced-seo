<?php

namespace Aerni\AdvancedSeo\Support;

use Statamic\Statamic;
use Illuminate\Support\Str;

class Helpers
{
    public static function parseLocale(string $locale): string
    {
        $parsed = preg_replace('/\.utf8/i', '', $locale);

        return \WhiteCube\Lingua\Service::create($parsed)->toW3C();
    }

    public static function isActionCpRoute(): bool
    {
        return Statamic::isCpRoute() && Str::contains(request()->path(), 'actions');
    }

    public static function isAddonCpRoute(): bool
    {
        return Statamic::isCpRoute() && Str::contains(request()->path(), 'advanced-seo');
    }

    public static function isBlueprintCpRoute(): bool
    {
        return Statamic::isCpRoute() && Str::contains(request()->path(), 'blueprints');
    }

    public static function isEntryCreateRoute(): bool
    {
        return request()->route()?->getName() === 'statamic.cp.collections.entries.create';
    }

    public static function isEntryEditRoute(): bool
    {
        return request()->route()?->getName() === 'statamic.cp.collections.entries.edit';
    }

    public static function isTermCreateRoute(): bool
    {
        return request()->route()?->getName() === 'statamic.cp.taxonomies.terms.create';
    }

    public static function isTermEditRoute(): bool
    {
        return request()->route()?->getName() === 'statamic.cp.taxonomies.terms.edit';
    }

    /**
     * Return true if we're on any custom route other than the defined exceptions below.
     * This includes any routes defined with `Route::get()` and `Route::statamic()`.
     */
    public static function isCustomRoute(): bool
    {
        $allowedControllerActions = collect([
            'Aerni\AdvancedSeo\Http\Controllers\Web\SocialImagesController@show',
            'Statamic\Http\Controllers\FrontendController@index',
        ]);

        $controllerAction = request()->route()?->getAction('controller');

        return $allowedControllerActions->doesntContain($controllerAction);
    }
}
