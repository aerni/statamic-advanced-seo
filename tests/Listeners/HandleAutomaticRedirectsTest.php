<?php

use Aerni\AdvancedSeo\Enums\RedirectType;
use Aerni\AdvancedSeo\Facades\Redirects;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
    ]);

    Collection::make('pages')->routes('/{slug}')->sites(['default'])->saveQuietly();
});

it('creates a permanent redirect pointing at the entry when the slug changes', function () {
    $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('old'))->save();

    $entry->slug('new')->save();

    $redirect = Redirects::query()->where('site', 'default')->where('source', '/old')->first();

    expect($redirect)->not->toBeNull()
        ->and($redirect->destination())->toBe("entry::{$entry->id()}")
        ->and($redirect->type())->toBe(RedirectType::Permanent)
        ->and($redirect->enabled())->toBeTrue()
        ->and($redirect->description())->toBe('Created automatically because the URL changed.');
});

it('creates a redirect when the date changes the url', function () {
    Collection::make('posts')->dated(true)->routes('/blog/{year}/{slug}')->sites(['default'])->saveQuietly();

    $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->date('2024-01-15'))->save();

    $entry->date('2025-06-20')->save();

    $redirect = Redirects::query()->where('site', 'default')->where('source', '/blog/2024/post')->first();

    expect($redirect)->not->toBeNull()
        ->and($redirect->destination())->toBe("entry::{$entry->id()}");
});

it('creates nothing when the date changes but the url stays the same', function () {
    Collection::make('posts')->dated(true)->routes('/blog/{year}/{slug}')->sites(['default'])->saveQuietly();

    $entry = tap(Entry::make()->collection('posts')->locale('default')->slug('post')->date('2024-01-15'))->save();

    $entry->date('2024-03-20')->save();

    expect(Redirects::query()->where('site', 'default')->get())->toHaveCount(0);
});

it('creates nothing when saving without a url change', function () {
    $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('old'))->save();

    $entry->set('title', 'Changed')->save();

    expect(Redirects::query()->where('site', 'default')->get())->toHaveCount(0);
});

it('creates nothing for a new entry', function () {
    tap(Entry::make()->collection('pages')->locale('default')->slug('fresh'))->save();

    expect(Redirects::query()->where('site', 'default')->get())->toHaveCount(0);
});

it('creates nothing when the collection toggle is off', function () {
    Seo::find('collections::pages')->config()->set('redirects', false)->save();

    $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('old'))->save();

    $entry->slug('new')->save();

    expect(Redirects::query()->where('site', 'default')->get())->toHaveCount(0);
});

it('creates nothing on the free edition', function () {
    useFreeEdition();

    $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('old'))->save();

    $entry->slug('new')->save();

    expect(Redirects::query()->where('site', 'default')->get())->toHaveCount(0);
});

it('leaves an existing redirect with the same source untouched', function () {
    Redirects::make()->source('/old')->destination('/custom')->site('default')->save();

    $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('old'))->save();

    $entry->slug('new')->save();

    $redirects = Redirects::query()->where('site', 'default')->where('source', '/old')->get();

    expect($redirects)->toHaveCount(1)
        ->and($redirects->first()->destination())->toBe('/custom');
});

it('repoints path destinations that referenced the old url to the entry', function () {
    Redirects::make()->source('/ancient')->destination('/old')->site('default')->save();

    $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('old'))->save();

    $entry->slug('new')->save();

    $repointed = Redirects::query()->where('site', 'default')->where('source', '/ancient')->first();

    expect($repointed->destination())->toBe("entry::{$entry->id()}");
});

it('deletes redirects that would shadow the new url', function () {
    $entry = tap(Entry::make()->collection('pages')->locale('default')->slug('a'))->save();

    $entry->slug('b')->save();
    $entry->slug('a')->save();

    $sources = Redirects::query()->where('site', 'default')->get()->map->source();

    expect($sources->all())->toBe(['/b']);
});
