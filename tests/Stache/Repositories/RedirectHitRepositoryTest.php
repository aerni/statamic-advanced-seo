<?php

use Aerni\AdvancedSeo\Contracts\RedirectHit as RedirectHitContract;
use Aerni\AdvancedSeo\Facades\Redirects;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

it('can make a redirect hit', function () {
    expect(Redirects::hits()->make())->toBeInstanceOf(RedirectHitContract::class);
});

it('returns null when finding a missing redirect hit', function () {
    expect(Redirects::hits()->find('missing'))->toBeNull();
});

it('can save and find a redirect hit', function () {
    Redirects::hits()->make()->redirect('abc')->count(3)->lastHitAt('2026-07-02 10:00:00')->save();

    clearStache();

    $found = Redirects::hits()->find('abc');

    expect($found)->toBeInstanceOf(RedirectHitContract::class)
        ->and($found->redirect())->toBe('abc')
        ->and($found->count())->toBe(3)
        ->and($found->lastHitAt())->toBe('2026-07-02 10:00:00');
});

it('can list all redirect hits', function () {
    Redirects::hits()->make()->redirect('a')->count(1)->save();
    Redirects::hits()->make()->redirect('b')->count(1)->save();

    expect(Redirects::hits()->all())->toHaveCount(2);
});

it('can delete a redirect hit', function () {
    $hit = Redirects::hits()->make()->redirect('abc')->count(1);
    $hit->save();

    $hit->delete();

    clearStache();

    expect(Redirects::hits()->find('abc'))->toBeNull();
});

it('records a hit for a new redirect', function () {
    Redirects::hits()->record('abc');

    $hit = Redirects::hits()->find('abc');

    expect($hit->count())->toBe(1)
        ->and($hit->lastHitAt())->not->toBeNull();
});

it('increments the count when recording a hit for an existing redirect', function () {
    Redirects::hits()->make()->redirect('abc')->count(4)->save();

    Redirects::hits()->record('abc');

    expect(Redirects::hits()->find('abc')->count())->toBe(5);
});

it('queries redirect hits by redirect id', function () {
    Redirects::hits()->make()->redirect('a')->count(1)->save();
    Redirects::hits()->make()->redirect('b')->count(1)->save();
    Redirects::hits()->make()->redirect('c')->count(1)->save();

    $ids = Redirects::hits()->query()
        ->whereIn('redirect', ['a', 'c'])
        ->get()
        ->map(fn ($hit) => $hit->redirect())
        ->sort()
        ->values()
        ->all();

    expect($ids)->toBe(['a', 'c']);
});
