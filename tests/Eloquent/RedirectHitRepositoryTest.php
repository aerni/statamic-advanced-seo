<?php

use Aerni\AdvancedSeo\Contracts\RedirectHit as RedirectHitContract;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;

uses(UseEloquentDriver::class);

it('can save and find a redirect hit via eloquent', function () {
    Redirect::hits()->make()->redirect('abc')->count(2)->lastHitAt(1751450400)->save();

    $found = Redirect::hits()->find('abc');

    expect($found)->toBeInstanceOf(RedirectHitContract::class)
        ->and($found->redirect())->toBe('abc')
        ->and($found->count())->toBe(2)
        ->and($found->lastHitAt())->toBe(1751450400);
});

it('can list and delete redirect hits via eloquent', function () {
    Redirect::hits()->make()->redirect('a')->count(1)->save();
    $b = Redirect::hits()->make()->redirect('b')->count(1);
    $b->save();

    expect(Redirect::hits()->all())->toHaveCount(2);

    $b->delete();

    expect(Redirect::hits()->all())->toHaveCount(1);
});

it('records a hit for a new redirect via eloquent', function () {
    Redirect::hits()->record('abc');

    $hit = Redirect::hits()->find('abc');

    expect($hit->count())->toBe(1)
        ->and($hit->lastHitAt())->not->toBeNull();
});

it('increments the count when recording a hit for an existing redirect via eloquent', function () {
    Redirect::hits()->make()->redirect('abc')->count(4)->save();

    Redirect::hits()->record('abc');

    expect(Redirect::hits()->find('abc')->count())->toBe(5);
});

it('queries redirect hits by redirect id via eloquent', function () {
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
