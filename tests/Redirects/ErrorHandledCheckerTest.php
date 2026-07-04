<?php

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

it('reports an exact match as handled', function () {
    Redirect::make()->source('/old')->destination('/new')->site('default')->save();

    $checker = ErrorHandledChecker::for(['default']);

    expect($checker->isHandled('/old', 'default'))->toBeTrue()
        ->and($checker->isHandled('/other', 'default'))->toBeFalse();
});

it('reports a wildcard match as handled', function () {
    Redirect::make()->source('/blog/*')->destination('/news/$1')->site('default')->save();

    $checker = ErrorHandledChecker::for(['default']);

    expect($checker->isHandled('/blog/post', 'default'))->toBeTrue();
});

it('does not treat a disabled redirect as handled', function () {
    Redirect::make()->source('/old')->destination('/new')->site('default')->enabled(false)->save();

    $checker = ErrorHandledChecker::for(['default']);

    expect($checker->isHandled('/old', 'default'))->toBeFalse();
});

it('scopes handled checks to the site', function () {
    Redirect::make()->source('/old')->destination('/new')->site('fr')->save();

    $checker = ErrorHandledChecker::for(['default', 'fr']);

    expect($checker->isHandled('/old', 'default'))->toBeFalse()
        ->and($checker->isHandled('/old', 'fr'))->toBeTrue();
});
