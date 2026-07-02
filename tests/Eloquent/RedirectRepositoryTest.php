<?php

use Aerni\AdvancedSeo\Contracts\Redirect as RedirectContract;
use Aerni\AdvancedSeo\Facades\Redirects;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;

uses(UseEloquentDriver::class);

it('can save and find a redirect via eloquent', function () {
    Redirects::make()->id('abc')->source('/old')->destination('/new')->site('default')->automatic(true)->save();

    $found = Redirects::find('abc');

    expect($found)->toBeInstanceOf(RedirectContract::class)
        ->and($found->source())->toBe('/old')
        ->and($found->site())->toBe('default')
        ->and($found->automatic())->toBeTrue();
});

it('can list and delete redirects via eloquent', function () {
    Redirects::make()->id('a')->source('/a')->destination('/x')->save();
    $b = Redirects::make()->id('b')->source('/b')->destination('/y');
    $b->save();

    expect(Redirects::all())->toHaveCount(2);

    $b->delete();

    expect(Redirects::all())->toHaveCount(1);
});

it('queries redirects by site', function () {
    Redirects::make()->id('a')->source('/a')->destination('/x')->site('default')->save();
    Redirects::make()->id('b')->source('/b')->destination('/y')->site('fr')->save();
    Redirects::make()->id('c')->source('/c')->destination('/z')->site('fr')->save();

    $ids = Redirects::query()->where('site', 'fr')->get()->map(fn ($r) => $r->id())->sort()->values()->all();

    expect($ids)->toBe(['b', 'c']);
});
