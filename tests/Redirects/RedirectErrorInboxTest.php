<?php

use Aerni\AdvancedSeo\Enums\ResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Redirects\RedirectErrorInbox;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesRedirects;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class, EnablesRedirects::class);

beforeEach(function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'fr' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);
});

it('deletes an exact error on the same site', function () {
    Redirect::errors()->make()->url('/old')->site('default')->save();
    $redirect = tap(Redirect::make()->source('/old')->destination('/new')->site('default'))->saveQuietly();

    app(RedirectErrorInbox::class)->deleteErrorsHandledBy($redirect);

    expect(Redirect::errors()->findByUrl('/old', 'default'))->toBeNull();
});

it('does not delete the same path on another site', function () {
    Redirect::errors()->make()->url('/old')->site('default')->save();
    Redirect::errors()->make()->url('/old')->site('fr')->save();
    $redirect = tap(Redirect::make()->source('/old')->destination('/new')->site('default'))->saveQuietly();

    app(RedirectErrorInbox::class)->deleteErrorsHandledBy($redirect);

    expect(Redirect::errors()->findByUrl('/old', 'default'))->toBeNull()
        ->and(Redirect::errors()->findByUrl('/old', 'fr'))->not->toBeNull();
});

it('deletes wildcard matches on the same site only', function () {
    Redirect::errors()->make()->url('/blog/a')->site('default')->save();
    Redirect::errors()->make()->url('/blog/a')->site('fr')->save();
    Redirect::errors()->make()->url('/other')->site('default')->save();
    $redirect = tap(Redirect::make()->source('/blog/*')->destination('/news/$1')->site('default'))->saveQuietly();

    app(RedirectErrorInbox::class)->deleteErrorsHandledBy($redirect);

    expect(Redirect::errors()->findByUrl('/blog/a', 'default'))->toBeNull()
        ->and(Redirect::errors()->findByUrl('/blog/a', 'fr'))->not->toBeNull()
        ->and(Redirect::errors()->findByUrl('/other', 'default'))->not->toBeNull();
});

it('does not delete when the redirect is disabled', function () {
    Redirect::errors()->make()->url('/old')->site('default')->save();
    $redirect = tap(Redirect::make()->source('/old')->destination('/new')->site('default')->enabled(false))->saveQuietly();

    app(RedirectErrorInbox::class)->deleteErrorsHandledBy($redirect);

    expect(Redirect::errors()->findByUrl('/old', 'default'))->not->toBeNull();
});

it('does not delete when the destination does not resolve', function () {
    Redirect::errors()->make()->url('/old')->site('default')->save();
    $redirect = tap(Redirect::make()->source('/old')->destination('entry::missing')->site('default'))->saveQuietly();

    app(RedirectErrorInbox::class)->deleteErrorsHandledBy($redirect);

    expect(Redirect::errors()->findByUrl('/old', 'default'))->not->toBeNull();
});

it('deletes when the redirect is gone', function () {
    Redirect::errors()->make()->url('/old')->site('default')->save();
    $redirect = tap(Redirect::make()->source('/old')->responseCode(ResponseCode::Gone)->site('default'))->saveQuietly();

    app(RedirectErrorInbox::class)->deleteErrorsHandledBy($redirect);

    expect(Redirect::errors()->findByUrl('/old', 'default'))->toBeNull();
});

it('deleteHandledErrors removes errors handled by any enabled redirect on their site', function () {
    Redirect::errors()->make()->url('/en')->site('default')->save();
    Redirect::errors()->make()->url('/fr')->site('fr')->save();
    Redirect::make()->source('/en')->destination('/new')->site('default')->saveQuietly();
    Redirect::make()->source('/fr')->destination('/nouveau')->site('fr')->enabled(false)->saveQuietly();

    app(RedirectErrorInbox::class)->deleteHandledErrors();

    expect(Redirect::errors()->findByUrl('/en', 'default'))->toBeNull()
        ->and(Redirect::errors()->findByUrl('/fr', 'fr'))->not->toBeNull();
});
