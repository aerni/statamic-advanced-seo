<?php

use Aerni\AdvancedSeo\Contracts\RedirectHit as RedirectHitContract;
use Aerni\AdvancedSeo\Facades\Redirect;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

it('can make a redirect hit', function () {
    expect(Redirect::hits()->make())->toBeInstanceOf(RedirectHitContract::class);
});

it('returns null when finding a missing redirect hit', function () {
    expect(Redirect::hits()->find('missing'))->toBeNull();
});

it('can save and find a redirect hit', function () {
    Redirect::hits()->make()->redirect('abc')->count(3)->lastHitAt(1751450400)->save();

    clearStache();

    $found = Redirect::hits()->find('abc');

    expect($found)->toBeInstanceOf(RedirectHitContract::class)
        ->and($found->redirect())->toBe('abc')
        ->and($found->count())->toBe(3)
        ->and($found->lastHitAt())->toBe(1751450400);
});

it('can list all redirect hits', function () {
    Redirect::hits()->make()->redirect('a')->count(1)->save();
    Redirect::hits()->make()->redirect('b')->count(1)->save();

    expect(Redirect::hits()->all())->toHaveCount(2);
});

it('can delete a redirect hit', function () {
    $hit = Redirect::hits()->make()->redirect('abc')->count(1);
    $hit->save();

    $hit->delete();

    clearStache();

    expect(Redirect::hits()->find('abc'))->toBeNull();
});

it('records a hit for a new redirect', function () {
    Redirect::hits()->record('abc');

    $hit = Redirect::hits()->find('abc');

    expect($hit->count())->toBe(1)
        ->and($hit->lastHitAt())->not->toBeNull();
});

it('increments the count when recording a hit for an existing redirect', function () {
    Redirect::hits()->make()->redirect('abc')->count(4)->save();

    Redirect::hits()->record('abc');

    expect(Redirect::hits()->find('abc')->count())->toBe(5);
});

it('queries redirect hits by redirect id', function () {
    Redirect::hits()->make()->redirect('a')->count(1)->save();
    Redirect::hits()->make()->redirect('b')->count(1)->save();
    Redirect::hits()->make()->redirect('c')->count(1)->save();

    $ids = Redirect::hits()->query()
        ->whereIn('redirect', ['a', 'c'])
        ->get()
        ->map(fn ($hit) => $hit->redirect())
        ->sort()
        ->values()
        ->all();

    expect($ids)->toBe(['a', 'c']);
});
