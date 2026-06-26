<?php

use Aerni\AdvancedSeo\Enums\MatchType;
use Aerni\AdvancedSeo\Enums\RedirectType;
use Aerni\AdvancedSeo\Redirects\Redirect;

it('generates an id when none is set', function () {
    $redirect = (new Redirect)->source('/old')->destination('/new');

    expect($redirect->id())->toBeString()->not->toBeEmpty();
});

it('keeps an explicitly set id', function () {
    $redirect = (new Redirect)->id('abc');

    expect($redirect->id())->toBe('abc');
});

it('exposes fluent accessors with sensible defaults', function () {
    $redirect = (new Redirect)
        ->source('/old')
        ->destination('/new')
        ->site('default');

    expect($redirect->type())->toBe(RedirectType::Permanent)
        ->and($redirect->matchType())->toBe(MatchType::Exact)
        ->and($redirect->enabled())->toBeTrue()
        ->and($redirect->site())->toBe('default');
});

it('normalizes a non-regex source on save', function () {
    expect((new Redirect)->source('/old/')->source())->toBe('/old')
        ->and((new Redirect)->source('blog/*/')->source())->toBe('/blog/*')
        ->and((new Redirect)->source('#^/p/(\d+)$#')->source())->toBe('#^/p/(\d+)$#');
});

it('lowercases a non-regex source on save but keeps regex case', function () {
    expect((new Redirect)->source('/Old-Page')->source())->toBe('/old-page')
        ->and((new Redirect)->source('/Blog/*')->source())->toBe('/blog/*')
        ->and((new Redirect)->source('#^/P/(\d+)$#')->source())->toBe('#^/P/(\d+)$#');
});

it('serializes only its fields to file data', function () {
    $redirect = (new Redirect)
        ->id('abc')
        ->source('/old')
        ->destination('/new')
        ->type(RedirectType::Temporary)
        ->site('french')
        ->enabled(false)
        ->description('Note');

    expect($redirect->fileData())->toBe([
        'source' => '/old',
        'destination' => '/new',
        'type' => 302,
        'enabled' => false,
        'description' => 'Note',
    ]);
});

it('builds its path from the redirects store directory, site, and id', function () {
    $redirect = (new Redirect)->id('abc')->site('fr');

    expect($redirect->path())->toEndWith('fr/abc.yaml');
});
