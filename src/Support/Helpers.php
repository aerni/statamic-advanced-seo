<?php

namespace Aerni\AdvancedSeo\Support;

use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Site;
use Statamic\Statamic;
use Statamic\Taxonomies\LocalizedTerm;

class Helpers
{
    /**
     * Ensure we have an augmentable content instance.
     *
     * The base Term class doesn't have augmented data access (HasAugmentedInstance trait),
     * only LocalizedTerm does. This method converts a base Term to its LocalizedTerm
     * for the current site context.
     */
    public static function localizedContent(Entry|Term $content): Entry|LocalizedTerm
    {
        if ($content instanceof Entry || $content instanceof LocalizedTerm) {
            return $content;
        }

        $site = Statamic::isCpRoute()
            ? request()->route('site')?->handle() ?? basename(request()->path())
            : Site::current()->handle();

        return $content->in($site);
    }

    public static function parseLocale(string $locale): string
    {
        $parsed = preg_replace('/\.utf8/i', '', $locale);

        return \WhiteCube\Lingua\Service::create($parsed)->toW3C();
    }

    public static function isAddonCpRoute(): bool
    {
        return Str::containsAll(request()->path(), [config('statamic.cp.route', 'cp'), 'advanced-seo']);
    }

    public static function isAiCpRoute(): bool
    {
        return Str::containsAll(request()->path(), [config('statamic.cp.route', 'cp'), 'advanced-seo', 'ai']);
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

        $controllerAction = request()->route()?->getAction('controller');

        return $allowedControllerActions->doesntContain($controllerAction);
    }
}
