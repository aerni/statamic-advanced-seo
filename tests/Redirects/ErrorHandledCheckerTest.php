<?php

use Aerni\AdvancedSeo\Enums\ResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Redirects\ErrorHandledChecker;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'fr' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);
});

it('matches an enabled exact redirect', function () {
    $redirect = tap(Redirect::make()->source('/old')->destination('/new')->site('default'))->save();

    $checker = ErrorHandledChecker::for(['default']);

    expect($checker->match('/old', 'default')?->id())->toBe($redirect->id())
        ->and($checker->match('/old', 'default')->enabled())->toBeTrue()
        ->and($checker->match('/other', 'default'))->toBeNull();
});

it('matches an enabled wildcard redirect', function () {
    $redirect = tap(Redirect::make()->source('/blog/*')->destination('/news/$1')->site('default'))->save();

    $checker = ErrorHandledChecker::for(['default']);

    expect($checker->match('/blog/post', 'default')?->id())->toBe($redirect->id());
});

it('still matches a disabled redirect but reports it as disabled', function () {
    $redirect = tap(Redirect::make()->source('/old')->destination('/new')->site('default')->enabled(false))->save();

    $match = ErrorHandledChecker::for(['default'])->match('/old', 'default');

    expect($match?->id())->toBe($redirect->id())
        ->and($match->enabled())->toBeFalse();
});

it('prefers an enabled redirect over a disabled one', function () {
    tap(Redirect::make()->source('/blog/*')->destination('/disabled')->site('default')->enabled(false))->save();
    $enabled = tap(Redirect::make()->source('/blog/post')->destination('/enabled')->site('default'))->save();

    $match = ErrorHandledChecker::for(['default'])->match('/blog/post', 'default');

    expect($match?->id())->toBe($enabled->id())
        ->and($match->enabled())->toBeTrue();
});

it('scopes matches to the site', function () {
    Redirect::make()->source('/old')->destination('/new')->site('fr')->save();

    $checker = ErrorHandledChecker::for(['default', 'fr']);

    expect($checker->match('/old', 'default'))->toBeNull()
        ->and($checker->match('/old', 'fr'))->not->toBeNull();
});

it('does not treat an enabled redirect with a dead destination as handled', function () {
    Redirect::make()->source('/old')->destination('entry::missing')->site('default')->save();

    expect(ErrorHandledChecker::for(['default'])->match('/old', 'default'))->toBeNull();
});

it('treats an enabled gone redirect as handled despite having no destination', function () {
    $redirect = tap(Redirect::make()->source('/old')->responseCode(ResponseCode::Gone)->site('default'))->save();

    expect(ErrorHandledChecker::for(['default'])->match('/old', 'default')?->id())->toBe($redirect->id());
});

it('prefers the most specific pattern when several match', function () {
    tap(Redirect::make()->source('#^/blog/.+#')->destination('/regex')->site('default'))->save();
    $wildcard = tap(Redirect::make()->source('/blog/*')->destination('/wildcard/$1')->site('default'))->save();

    expect(ErrorHandledChecker::for(['default'])->match('/blog/post', 'default')?->id())->toBe($wildcard->id());
});
