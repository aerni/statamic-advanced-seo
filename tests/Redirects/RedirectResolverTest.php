<?php

use Aerni\AdvancedSeo\Enums\RedirectResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Redirects\RedirectResolver;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'french' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);
});

it('matches an exact rule for the site', function () {
    Redirect::make()->source('/old')->destination('/new')->site('default')->save();

    $resolved = RedirectResolver::resolve('/old', 'default');

    expect($resolved->responseCode)->toBe(RedirectResponseCode::Permanent)->and($resolved->destination)->toBe('/new');
});

it('matches an exact rule regardless of trailing slashes', function () {
    Redirect::make()->source('/old/')->destination('/new')->site('default')->save();

    expect(RedirectResolver::resolve('/old', 'default')->destination)->toBe('/new')
        ->and(RedirectResolver::resolve('/old/', 'default')->destination)->toBe('/new');
});

it('does not match a rule for a different site', function () {
    Redirect::make()->source('/old')->destination('/new')->site('french')->save();

    expect(RedirectResolver::resolve('/old', 'default'))->toBeNull();
});

it('ignores disabled rules', function () {
    Redirect::make()->source('/old')->destination('/new')->enabled(false)->site('default')->save();

    expect(RedirectResolver::resolve('/old', 'default'))->toBeNull();
});

it('substitutes wildcard captures into the destination', function () {
    Redirect::make()->source('/blog/*')->destination('/news/$1')->site('default')->save();

    $resolved = RedirectResolver::resolve('/blog/hello-world', 'default');

    expect($resolved->destination)->toBe('/news/hello-world');
});

it('substitutes regex captures into the destination', function () {
    Redirect::make()->source('#^/p/(\d+)$#')->destination('/products/$1')->site('default')->save();

    $resolved = RedirectResolver::resolve('/p/42', 'default');

    expect($resolved->destination)->toBe('/products/42');
});

it('prefers an exact match over a pattern match', function () {
    Redirect::make()->source('/a/*')->destination('/pattern')->site('default')->save();
    Redirect::make()->source('/a/b')->destination('/exact')->site('default')->save();

    expect(RedirectResolver::resolve('/a/b', 'default')->destination)->toBe('/exact');
});

it('prefers the more specific wildcard among overlapping patterns', function () {
    Redirect::make()->source('/*/*')->destination('/broad/$1/$2')->site('default')->save();
    Redirect::make()->source('/blog/*')->destination('/specific/$1')->site('default')->save();

    expect(RedirectResolver::resolve('/blog/hello', 'default')->destination)->toBe('/specific/hello');
});

it('matches an exact rule case-insensitively', function () {
    Redirect::make()->source('/Old-Page')->destination('/new')->site('default')->save();

    expect(RedirectResolver::resolve('/old-page', 'default')->destination)->toBe('/new')
        ->and(RedirectResolver::resolve('/OLD-PAGE', 'default')->destination)->toBe('/new');
});

it('matches a wildcard case-insensitively and preserves the captured case', function () {
    Redirect::make()->source('/blog/*')->destination('/news/$1')->site('default')->save();

    expect(RedirectResolver::resolve('/Blog/My-Post', 'default')->destination)->toBe('/news/My-Post');
});

it('prefers a specific wildcard over a catch-all regex', function () {
    Redirect::make()->source('#^/.*$#')->destination('/regex')->site('default')->save();
    Redirect::make()->source('/section/*')->destination('/wildcard/$1')->site('default')->save();

    expect(RedirectResolver::resolve('/section/x', 'default')->destination)->toBe('/wildcard/x');
});

it('does not substitute capture placeholders in an exact destination', function () {
    Redirect::make()->source('/old')->destination('/new$1')->site('default')->save();

    expect(RedirectResolver::resolve('/old', 'default')->destination)->toBe('/new$1');
});

it('matches a wildcard segment without crossing slashes', function () {
    Redirect::make()->source('/blog/*')->destination('/news/$1')->site('default')->save();

    expect(RedirectResolver::resolve('/blog/a/b', 'default'))->toBeNull();
});

it('returns a 410 match with no destination', function () {
    Redirect::make()->source('/gone')->responseCode(RedirectResponseCode::Gone)->site('default')->save();

    $resolved = RedirectResolver::resolve('/gone', 'default');

    expect($resolved->responseCode)->toBe(RedirectResponseCode::Gone)->and($resolved->destination)->toBeNull();
});

it('substitutes multiple regex captures', function () {
    Redirect::make()->source('#^/(\w+)/(\d+)$#')->destination('/$1/item/$2')->site('default')->save();

    expect(RedirectResolver::resolve('/cat/42', 'default')->destination)->toBe('/cat/item/42');
});

it('does not fall through to a pattern when the matched exact rule cannot resolve', function () {
    Redirect::make()->source('/x')->destination('entry::missing')->site('default')->save();
    Redirect::make()->source('/*')->destination('/catch')->site('default')->save();

    expect(RedirectResolver::resolve('/x', 'default'))->toBeNull();
});

it('resolves an entry destination to its absolute url', function () {
    Site::setSites(['default' => ['name' => 'Default', 'url' => 'https://example.com', 'locale' => 'en']]);

    Collection::make('pages')->routes('/{slug}')->sites(['default'])->saveQuietly();

    $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('about')->data(['title' => 'About']))->save();

    Redirect::make()->source('/old')->destination("entry::{$entry->id()}")->site('default')->save();

    expect(RedirectResolver::resolve('/old', 'default')->destination)->toBe('https://example.com/about');
});

it('ignores a malformed regex rule', function () {
    Redirect::make()->source('#^/p/(\d+$#')->destination('/p/$1')->site('default')->save();

    expect(RedirectResolver::resolve('/p/1', 'default'))->toBeNull();
});

it('exposes the matched redirect id on the resolved redirect', function () {
    Redirect::make()->id('abc')->source('/old')->destination('/new')->site('default')->save();

    $resolved = RedirectResolver::resolve('/old', 'default');

    expect($resolved->id)->toBe('abc');
});

it('exposes the matched redirect id for a gone redirect', function () {
    Redirect::make()->id('gone-1')->source('/gone')->responseCode(RedirectResponseCode::Gone)->site('default')->save();

    $resolved = RedirectResolver::resolve('/gone', 'default');

    expect($resolved->id)->toBe('gone-1');
});
