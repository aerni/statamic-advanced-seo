<?php

use Aerni\AdvancedSeo\Enums\RedirectSourceType;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Redirects\RedirectResolver;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;
use Illuminate\Support\Facades\DB;

uses(UseEloquentDriver::class);

it('queries redirects by source through the indexed source lookup via eloquent', function () {
    $source = '/'.str_repeat('long-source-', 30);

    Redirect::make()->id('match')->source($source)->destination('/new')->site('default')->save();
    Redirect::make()->id('other')->source('/other')->destination('/new')->site('default')->save();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $redirect = Redirect::query()->whereSource("{$source}/")->first();
    $query = collect(DB::getQueryLog())->last()['query'];

    expect($redirect->id())->toBe('match')
        ->and($query)->toContain('source_hash')
        ->and($query)->toContain('source');
});

it('queries redirects by their inferred source type via eloquent', function () {
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

it('uses indexed source metadata when resolving redirects via eloquent', function () {
    Redirect::make()->id('exact')->source('/exact')->destination('/new')->site('default')->save();
    Redirect::make()->id('wildcard')->source('/wildcard/*')->destination('/new')->site('default')->save();

    DB::flushQueryLog();
    DB::enableQueryLog();

    expect(RedirectResolver::resolve('/missing', 'default'))->toBeNull();

    $queries = collect(DB::getQueryLog())->pluck('query')->implode("\n");

    expect($queries)->toContain('source_hash')
        ->and($queries)->toContain('source_type');
});

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
