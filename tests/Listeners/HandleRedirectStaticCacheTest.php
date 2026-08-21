<?php

use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesRedirects;
use Illuminate\Http\Request;
use Statamic\Facades\Site;
use Statamic\StaticCaching\Cacher;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class, EnablesRedirects::class);

beforeEach(function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
    ]);

    config(['statamic.static_caching.strategy' => 'half']);
});

it('invalidates a cached url when an exact redirect is saved', function () {
    $cacher = cachePages('/old');

    Redirect::make()->source('/old')->destination('/new')->site('default')->save();

    expect(cachedPaths($cacher))->not->toContain('/old');
});

it('does not invalidate unrelated cached urls when an exact redirect is saved', function () {
    $cacher = cachePages('/old', '/about');

    Redirect::make()->source('/old')->destination('/new')->site('default')->save();

    expect(cachedPaths($cacher))
        ->not->toContain('/old')
        ->toContain('/about');
});

it('does not invalidate when static caching is disabled', function () {
    $cacher = cachePages('/old');

    config(['statamic.static_caching.strategy' => null]);

    Redirect::make()->source('/old')->destination('/new')->site('default')->save();

    expect(cachedPaths($cacher))->toContain('/old');
});

it('invalidates matching cached urls when a wildcard redirect is saved', function () {
    $cacher = cachePages('/blog/hello', '/blog/hello/nested', '/about');

    Redirect::make()->source('/blog/*')->destination('/articles/$1')->site('default')->save();

    expect(cachedPaths($cacher))
        ->not->toContain('/blog/hello')
        ->toContain('/blog/hello/nested')
        ->toContain('/about');
});

it('invalidates matching cached urls when a regex redirect is saved', function () {
    $cacher = cachePages('/wp-login.php', '/about');

    Redirect::make()->source('#\.php$#')->destination('/')->site('default')->save();

    expect(cachedPaths($cacher))
        ->not->toContain('/wp-login.php')
        ->toContain('/about');
});

it('only invalidates the matching site when a redirect is saved', function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'french' => ['name' => 'French', 'url' => '/fr', 'locale' => 'fr'],
    ]);

    $cacher = cachePages('/old', '/fr/old');

    Redirect::make()->source('/old')->destination('/new')->site('french')->save();

    expect(cachedPaths($cacher))
        ->toContain('/old')
        ->not->toContain('/fr/old');
});

function cachePages(string ...$paths): Cacher
{
    $cacher = app(Cacher::class);

    foreach ($paths as $path) {
        $cacher->cachePage(Request::create(url($path)), response('not found', 404));
    }

    expect(cachedPaths($cacher))->toEqualCanonicalizing($paths);

    return $cacher;
}

function cachedPaths(Cacher $cacher): array
{
    return $cacher->getUrls()->values()->all();
}
