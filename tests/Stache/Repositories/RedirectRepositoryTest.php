<?php

use Aerni\AdvancedSeo\Contracts\Redirect as RedirectContract;
use Aerni\AdvancedSeo\Events\RedirectCreated;
use Aerni\AdvancedSeo\Events\RedirectSaved;
use Aerni\AdvancedSeo\Facades\Redirects;
use Illuminate\Support\Facades\Event;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

it('can make a redirect', function () {
    expect(Redirects::make())->toBeInstanceOf(RedirectContract::class);
});

it('returns null when finding a missing redirect', function () {
    expect(Redirects::find('missing'))->toBeNull();
});

it('can save and find a redirect', function () {
    Redirects::make()->id('abc')->source('/old')->destination('/new')->site('default')->save();

    clearStache();

    $found = Redirects::find('abc');

    expect($found)->toBeInstanceOf(RedirectContract::class)
        ->and($found->source())->toBe('/old')
        ->and($found->destination())->toBe('/new')
        ->and($found->site())->toBe('default');
});

it('can list all redirects', function () {
    Redirects::make()->id('a')->source('/a')->destination('/x')->save();
    Redirects::make()->id('b')->source('/b')->destination('/y')->save();

    expect(Redirects::all())->toHaveCount(2);
});

it('can delete a redirect', function () {
    $redirect = Redirects::make()->id('abc')->source('/old')->destination('/new');
    $redirect->save();

    $redirect->delete();

    clearStache();

    expect(Redirects::find('abc'))->toBeNull();
});

it('dispatches RedirectCreated only on the first save', function () {
    Event::fake([RedirectCreated::class, RedirectSaved::class]);

    $redirect = Redirects::make()->id('abc')->source('/old')->destination('/new')->site('default');
    $redirect->save();
    $redirect->save();

    Event::assertDispatched(RedirectCreated::class, 1);
    Event::assertDispatched(RedirectSaved::class, 2);
});

it('queries redirects by site', function () {
    Redirects::make()->id('a')->source('/a')->destination('/x')->site('default')->save();
    Redirects::make()->id('b')->source('/b')->destination('/y')->site('fr')->save();
    Redirects::make()->id('c')->source('/c')->destination('/z')->site('fr')->save();

    $ids = Redirects::query()->where('site', 'fr')->get()->map(fn ($r) => $r->id())->sort()->values()->all();

    expect($ids)->toBe(['b', 'c']);
});
