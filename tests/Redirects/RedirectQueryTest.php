<?php

use Aerni\AdvancedSeo\Enums\RedirectSourceType;
use Aerni\AdvancedSeo\Facades\Redirect;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

it('queries redirects by source through the indexed source lookup', function () {
    $source = '/'.str_repeat('long-source-', 30);

    Redirect::make()->id('match')->source($source)->destination('/new')->site('default')->save();
    Redirect::make()->id('other')->source('/other')->destination('/new')->site('default')->save();

    expect(Redirect::query()->whereSource("{$source}/")->first()->id())->toBe('match');
});

it('queries redirects by their inferred source type', function () {
    Redirect::make()->id('exact')->source('/exact')->destination('/new')->site('default')->save();
    Redirect::make()->id('wildcard')->source('/wildcard/*')->destination('/new')->site('default')->save();
    Redirect::make()->id('regex')->source('#^/regex/(.*)$#')->destination('/new')->site('default')->save();

    $ids = Redirect::query()
        ->whereIn('source_type', [RedirectSourceType::Wildcard->value, RedirectSourceType::Regex->value])
        ->get()
        ->map->id()
        ->sort()
        ->values()
        ->all();

    expect($ids)->toBe(['regex', 'wildcard']);
});

it('orders redirects by hit count', function () {
    Redirect::make()->id('r1')->source('/a')->destination('/x')->site('default')->save();
    Redirect::make()->id('r2')->source('/b')->destination('/y')->site('default')->save();
    Redirect::make()->id('r3')->source('/c')->destination('/z')->site('default')->save();

    Redirect::hits()->make()->redirect('r1')->count(5)->save();
    Redirect::hits()->make()->redirect('r2')->count(50)->save();

    $ids = Redirect::query()->orderBy('hits', 'desc')->get()->map->id()->all();

    expect($ids)->toBe(['r2', 'r1', 'r3']);
});

it('orders redirects by last hit', function () {
    Redirect::make()->id('r1')->source('/a')->destination('/x')->site('default')->save();
    Redirect::make()->id('r2')->source('/b')->destination('/y')->site('default')->save();
    Redirect::make()->id('r3')->source('/c')->destination('/z')->site('default')->save();

    Redirect::hits()->make()->redirect('r1')->count(1)->lastHitAt(1000)->save();
    Redirect::hits()->make()->redirect('r2')->count(1)->lastHitAt(9000)->save();

    $ids = Redirect::query()->orderBy('last_hit_at', 'desc')->get()->map->id()->all();

    expect($ids)->toBe(['r2', 'r1', 'r3']);
});

it('orders by hit count within a site filter', function () {
    Redirect::make()->id('en-low')->source('/a')->destination('/x')->site('default')->save();
    Redirect::make()->id('en-high')->source('/b')->destination('/y')->site('default')->save();
    Redirect::make()->id('fr-highest')->source('/c')->destination('/z')->site('fr')->save();

    Redirect::hits()->make()->redirect('en-low')->count(5)->save();
    Redirect::hits()->make()->redirect('en-high')->count(20)->save();
    Redirect::hits()->make()->redirect('fr-highest')->count(100)->save();

    $ids = Redirect::query()->where('site', 'default')->orderBy('hits', 'desc')->get()->map->id()->all();

    expect($ids)->toBe(['en-high', 'en-low']);
});
