<?php

use Aerni\AdvancedSeo\Contracts\Redirect as RedirectContract;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;

uses(UseEloquentDriver::class);

it('can save and find a redirect via eloquent', function () {
    Redirect::make()->id('abc')->source('/old')->destination('/new')->site('default')->automatic(true)->save();

    $found = Redirect::find('abc');

    expect($found)->toBeInstanceOf(RedirectContract::class)
        ->and($found->source())->toBe('/old')
        ->and($found->site())->toBe('default')
        ->and($found->automatic())->toBeTrue();
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

it('exposes its associated hit record via eloquent', function () {
    Redirect::make()->id('r1')->source('/old')->destination('/new')->save();
    Redirect::hits()->make()->redirect('r1')->count(5)->save();

    expect(Redirect::find('r1')->hit()?->count())->toBe(5);
});
