<?php

use Aerni\AdvancedSeo\Contracts\Redirect as RedirectContract;
use Aerni\AdvancedSeo\Enums\Origin;
use Aerni\AdvancedSeo\Events\RedirectCreated;
use Aerni\AdvancedSeo\Events\RedirectSaved;
use Aerni\AdvancedSeo\Facades\Redirect;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

it('can make a redirect', function () {
    expect(Redirect::make())->toBeInstanceOf(RedirectContract::class);
});

it('returns null when finding a missing redirect', function () {
    expect(Redirect::find('missing'))->toBeNull();
});

it('can save and find a redirect', function () {
    Redirect::make()->id('abc')->source('/old')->destination('/new')->site('default')->origin(Origin::Automatic)->save();

    clearStache();

    $found = Redirect::find('abc');

    expect($found)->toBeInstanceOf(RedirectContract::class)
        ->and($found->source())->toBe('/old')
        ->and($found->destination())->toBe('/new')
        ->and($found->site())->toBe('default')
        ->and($found->origin())->toBe(Origin::Automatic);
});

it('persists created_at through save and find', function () {
    Carbon::setTestNow('2026-07-04 12:00:00');

    Redirect::make()->id('abc')->source('/old')->destination('/new')->site('default')->save();

    clearStache();

    expect(Redirect::find('abc')->createdAt())->toBe(Carbon::parse('2026-07-04 12:00:00')->timestamp);
});

it('can list all redirects', function () {
    Redirect::make()->id('a')->source('/a')->destination('/x')->save();
    Redirect::make()->id('b')->source('/b')->destination('/y')->save();

    expect(Redirect::all())->toHaveCount(2);
});

it('can delete a redirect', function () {
    $redirect = Redirect::make()->id('abc')->source('/old')->destination('/new');
    $redirect->save();

    $redirect->delete();

    clearStache();

    expect(Redirect::find('abc'))->toBeNull();
});

it('dispatches RedirectCreated only on the first save', function () {
    Event::fake([RedirectCreated::class, RedirectSaved::class]);

    $redirect = Redirect::make()->id('abc')->source('/old')->destination('/new')->site('default');
    $redirect->save();
    $redirect->save();

    Event::assertDispatched(RedirectCreated::class, 1);
    Event::assertDispatched(RedirectSaved::class, 2);
});

it('queries redirects by site', function () {
    Redirect::make()->id('a')->source('/a')->destination('/x')->site('default')->save();
    Redirect::make()->id('b')->source('/b')->destination('/y')->site('fr')->save();
    Redirect::make()->id('c')->source('/c')->destination('/z')->site('fr')->save();

    $ids = Redirect::query()->where('site', 'fr')->get()->map(fn ($r) => $r->id())->sort()->values()->all();

    expect($ids)->toBe(['b', 'c']);
});
