<?php

namespace Aerni\AdvancedSeo\Support;

use Illuminate\Support\Str;

class Helpers
{
    public static function parseLocale(string $locale): string
    {
        $parsed = preg_replace('/\.utf8/i', '', $locale);

        return \WhiteCube\Lingua\Service::create($parsed)->toW3C();
    }

    public static function isAddonCpRoute(): bool
    {
        return Str::containsAll(request()->path(), [config('statamic.cp.route', 'cp'), 'advanced-seo']);
    }

    public static function isBlueprintCpRoute(): bool
    {
        return Str::containsAll(request()->path(), [config('statamic.cp.route', 'cp'), 'blueprints']);
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

        $controllerAction = request()->route()->getAction('controller');

        return $allowedControllerActions->doesntContain($controllerAction);
    }
}
