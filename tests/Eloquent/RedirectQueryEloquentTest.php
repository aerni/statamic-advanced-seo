<?php

use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;

uses(UseEloquentDriver::class);

it('orders redirects by hit count via eloquent', function () {
    Redirect::make()->id('r1')->source('/a')->destination('/x')->site('default')->save();
    Redirect::make()->id('r2')->source('/b')->destination('/y')->site('default')->save();
    Redirect::make()->id('r3')->source('/c')->destination('/z')->site('default')->save();

    Redirect::hits()->make()->redirect('r1')->count(5)->save();
    Redirect::hits()->make()->redirect('r2')->count(50)->save();

    $ids = Redirect::query()->orderBy('hits', 'desc')->get()->map->id()->all();

    expect($ids)->toBe(['r2', 'r1', 'r3']);
});

it('orders redirects by last hit via eloquent', function () {
    Redirect::make()->id('r1')->source('/a')->destination('/x')->site('default')->save();
    Redirect::make()->id('r2')->source('/b')->destination('/y')->site('default')->save();
    Redirect::make()->id('r3')->source('/c')->destination('/z')->site('default')->save();

    Redirect::hits()->make()->redirect('r1')->count(1)->lastHitAt(1000)->save();
    Redirect::hits()->make()->redirect('r2')->count(1)->lastHitAt(9000)->save();

    $ids = Redirect::query()->orderBy('last_hit_at', 'desc')->get()->map->id()->all();

    expect($ids)->toBe(['r2', 'r1', 'r3']);
});

it('orders by hit count within a site filter via eloquent', function () {
    Redirect::make()->id('en-low')->source('/a')->destination('/x')->site('default')->save();
    Redirect::make()->id('en-high')->source('/b')->destination('/y')->site('default')->save();
    Redirect::make()->id('fr-highest')->source('/c')->destination('/z')->site('fr')->save();

    Redirect::hits()->make()->redirect('en-low')->count(5)->save();
    Redirect::hits()->make()->redirect('en-high')->count(20)->save();
    Redirect::hits()->make()->redirect('fr-highest')->count(100)->save();

    $ids = Redirect::query()->where('site', 'default')->orderBy('hits', 'desc')->get()->map->id()->all();

    expect($ids)->toBe(['en-high', 'en-low']);
});

it('can filter on created_at while ordering by hits via eloquent', function () {
    Redirect::make()->id('r1')->source('/a')->destination('/x')->site('default')->save();
    Redirect::make()->id('r2')->source('/b')->destination('/y')->site('default')->save();

    Redirect::hits()->make()->redirect('r1')->count(5)->save();
    Redirect::hits()->make()->redirect('r2')->count(50)->save();

    $ids = Redirect::query()
        ->whereNotNull('created_at')
        ->orderBy('hits', 'desc')
        ->get()
        ->map->id()
        ->all();

    expect($ids)->toBe(['r2', 'r1']);
});
