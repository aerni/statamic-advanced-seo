<?php

use Aerni\AdvancedSeo\Contracts\Redirect as RedirectContract;
use Aerni\AdvancedSeo\Enums\Origin;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;
use Illuminate\Support\Carbon;

uses(UseEloquentDriver::class);

it('can save and find a redirect via eloquent', function () {
    Redirect::make()->id('abc')->source('/old')->destination('/new')->site('default')->origin(Origin::Automatic)->save();

    $found = Redirect::find('abc');

    expect($found)->toBeInstanceOf(RedirectContract::class)
        ->and($found->source())->toBe('/old')
        ->and($found->site())->toBe('default')
        ->and($found->origin())->toBe(Origin::Automatic);
});

it('can list and delete redirects via eloquent', function () {
    Redirect::make()->id('a')->source('/a')->destination('/x')->save();
    $b = Redirect::make()->id('b')->source('/b')->destination('/y');
    $b->save();

    expect(Redirect::all())->toHaveCount(2);

    $b->delete();

    expect(Redirect::all())->toHaveCount(1);
});

it('queries redirects by site', function () {
    Redirect::make()->id('a')->source('/a')->destination('/x')->site('default')->save();
    Redirect::make()->id('b')->source('/b')->destination('/y')->site('fr')->save();
    Redirect::make()->id('c')->source('/c')->destination('/z')->site('fr')->save();

    $ids = Redirect::query()->where('site', 'fr')->get()->map(fn ($r) => $r->id())->sort()->values()->all();

    expect($ids)->toBe(['b', 'c']);
});

it('stamps created_at on a new redirect via eloquent', function () {
    Carbon::setTestNow('2026-07-04 12:00:00');

    Redirect::make()->id('abc')->source('/old')->destination('/new')->site('default')->save();

    expect(Redirect::find('abc')->createdAt())->toBe(Carbon::parse('2026-07-04 12:00:00')->timestamp);
});

it('preserves an explicitly set created_at via eloquent', function () {
    Carbon::setTestNow('2026-07-04 12:00:00');

    $imported = Carbon::parse('2020-01-01 00:00:00')->timestamp;

    Redirect::make()->id('abc')->source('/old')->destination('/new')->site('default')->createdAt($imported)->save();

    expect(Redirect::find('abc')->createdAt())->toBe($imported);
});

it('exposes its associated hit record via eloquent', function () {
    Redirect::make()->id('r1')->source('/old')->destination('/new')->save();
    Redirect::hits()->make()->redirect('r1')->count(5)->save();

    expect(Redirect::find('r1')->hit()?->count())->toBe(5);
});
