<?php

use Aerni\AdvancedSeo\Enums\MatchType;
use Aerni\AdvancedSeo\Enums\RedirectType;
use Aerni\AdvancedSeo\Redirects\Redirect;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

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
        ->and($redirect->forwardQueryString())->toBeTrue()
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
        'forward_query_string' => true,
        'description' => 'Note',
    ]);
});

it('does not persist a destination or query string forwarding for a gone redirect', function () {
    $redirect = (new Redirect)
        ->id('abc')
        ->source('/old')
        ->type(RedirectType::Gone)
        ->site('default');

    expect($redirect->fileData()['destination'])->toBeNull()
        ->and($redirect->fileData()['forward_query_string'])->toBeNull();
});

it('builds its path from the redirects store directory, site, and id', function () {
    $redirect = (new Redirect)->id('abc')->site('fr');

    expect($redirect->path())->toEndWith('fr/abc.yaml');
});

it('treats a slashless destination as a site-relative path', function () {
    Site::setSites([
        'default' => ['name' => 'English', 'url' => 'http://localhost', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'http://localhost/de', 'locale' => 'de'],
    ]);

    $default = (new Redirect)->destination('new')->site('default');
    $german = (new Redirect)->destination('new')->site('german');

    expect($default->destinationUrl())->toBe('http://localhost/new')
        ->and($german->destinationUrl())->toBe('http://localhost/de/new');
});

it('does not alter a leading-slash path destination url', function () {
    Site::setSites(['default' => ['name' => 'English', 'url' => 'http://localhost', 'locale' => 'en']]);

    $redirect = (new Redirect)->destination('/new')->site('default');

    expect($redirect->destinationUrl())->toBe('http://localhost/new');
});

it('passes an external destination through unchanged in destinationUrl', function () {
    Site::setSites(['default' => ['name' => 'English', 'url' => 'http://localhost', 'locale' => 'en']]);

    expect((new Redirect)->destination('https://example.com/x')->site('default')->destinationUrl())
        ->toBe('https://example.com/x');

    expect((new Redirect)->destination('http://example.com/x')->site('default')->destinationUrl())
        ->toBe('http://example.com/x');
});

it('returns null from destinationUrl when destination is null', function () {
    Site::setSites(['default' => ['name' => 'English', 'url' => 'http://localhost', 'locale' => 'en']]);

    expect((new Redirect)->destination(null)->site('default')->destinationUrl())->toBeNull();
});

it('builds an absolute sourceUrl for an exact redirect using the site absoluteUrl', function () {
    Site::setSites([
        'default' => ['name' => 'English', 'url' => 'http://localhost', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'http://localhost/de', 'locale' => 'de'],
    ]);

    $redirect = (new Redirect)->source('/old')->site('german');

    expect($redirect->sourceUrl())->toBe('http://localhost/de/old');
});

it('substitutes wildcard segments with sample values in sourceUrl', function () {
    Site::setSites(['default' => ['name' => 'English', 'url' => 'http://localhost', 'locale' => 'en']]);

    expect((new Redirect)->source('/blog/*')->site('default')->sourceUrl())->toBe('http://localhost/blog/wildcard1');
    expect((new Redirect)->source('/a/*/b/*')->site('default')->sourceUrl())->toBe('http://localhost/a/wildcard1/b/wildcard2');
});

it('returns null from sourceUrl for a regex redirect', function () {
    Site::setSites(['default' => ['name' => 'English', 'url' => 'http://localhost', 'locale' => 'en']]);

    expect((new Redirect)->source('#^/x$#')->site('default')->sourceUrl())->toBeNull();
});

it('resolves an entry destination to the selected localization, not the redirect site', function () {
    Site::setSites([
        'default' => ['name' => 'English', 'url' => 'http://localhost/', 'locale' => 'en'],
        'french' => ['name' => 'French', 'url' => 'http://fr.localhost/', 'locale' => 'fr'],
    ]);

    Collection::make('pages')->routes('/{slug}')->sites(['default', 'french'])->saveQuietly();

    $entry = tap(Entry::make()->collection('pages')->locale('french')->slug('accueil')->published(true))->save();

    $redirect = (new Redirect)->destination("entry::{$entry->id()}")->site('default');

    expect($redirect->destinationUrl())->toBe('http://fr.localhost/accueil');
});
