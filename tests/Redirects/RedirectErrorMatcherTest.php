<?php

use Aerni\AdvancedSeo\Enums\RedirectResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Redirects\RedirectErrorMatcher;
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

    $matcher = RedirectErrorMatcher::for(['default']);

    expect($matcher->match('/old', 'default')?->id())->toBe($redirect->id())
        ->and($matcher->match('/old', 'default')->enabled())->toBeTrue()
        ->and($matcher->match('/other', 'default'))->toBeNull();
});

it('matches an enabled wildcard redirect', function () {
    $redirect = tap(Redirect::make()->source('/blog/*')->destination('/news/$1')->site('default'))->save();

    $matcher = RedirectErrorMatcher::for(['default']);

    expect($matcher->match('/blog/post', 'default')?->id())->toBe($redirect->id());
});

it('still matches a disabled redirect but reports it as disabled', function () {
    $redirect = tap(Redirect::make()->source('/old')->destination('/new')->site('default')->enabled(false))->save();

    $match = RedirectErrorMatcher::for(['default'])->match('/old', 'default');

    expect($match?->id())->toBe($redirect->id())
        ->and($match->enabled())->toBeFalse();
});

it('prefers an enabled redirect over a disabled one', function () {
    tap(Redirect::make()->source('/blog/*')->destination('/disabled')->site('default')->enabled(false))->save();
    $enabled = tap(Redirect::make()->source('/blog/post')->destination('/enabled')->site('default'))->save();

    $match = RedirectErrorMatcher::for(['default'])->match('/blog/post', 'default');

    expect($match?->id())->toBe($enabled->id())
        ->and($match->enabled())->toBeTrue();
});

it('scopes matches to the site', function () {
    Redirect::make()->source('/old')->destination('/new')->site('fr')->save();

    $matcher = RedirectErrorMatcher::for(['default', 'fr']);

    expect($matcher->match('/old', 'default'))->toBeNull()
        ->and($matcher->match('/old', 'fr'))->not->toBeNull();
});

it('does not treat an enabled redirect with a dead destination as handled', function () {
    Redirect::make()->source('/old')->destination('entry::missing')->site('default')->save();

    expect(RedirectErrorMatcher::for(['default'])->match('/old', 'default'))->toBeNull();
});

it('treats an enabled gone redirect as handled despite having no destination', function () {
    $redirect = tap(Redirect::make()->source('/old')->responseCode(RedirectResponseCode::Gone)->site('default'))->save();

    expect(RedirectErrorMatcher::for(['default'])->match('/old', 'default')?->id())->toBe($redirect->id());
});

it('does not match a missing or empty url', function () {
    tap(Redirect::make()->source('/old')->destination('/new')->site('default'))->save();
    tap(Redirect::make()->source('/*')->destination('/catch')->site('default'))->save();

    $matcher = RedirectErrorMatcher::for(['default']);

    expect($matcher->match(null, 'default'))->toBeNull()
        ->and($matcher->match('', 'default'))->toBeNull();
});

it('prefers the most specific pattern when several match', function () {
    tap(Redirect::make()->source('#^/blog/.+#')->destination('/regex')->site('default'))->save();
    $wildcard = tap(Redirect::make()->source('/blog/*')->destination('/wildcard/$1')->site('default'))->save();

    expect(RedirectErrorMatcher::for(['default'])->match('/blog/post', 'default')?->id())->toBe($wildcard->id());
});
