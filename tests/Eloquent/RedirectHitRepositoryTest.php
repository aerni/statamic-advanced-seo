<?php

use Aerni\AdvancedSeo\Contracts\RedirectHit as RedirectHitContract;
use Aerni\AdvancedSeo\Facades\Redirects;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;

uses(UseEloquentDriver::class);

it('can save and find a redirect hit via eloquent', function () {
    Redirects::hits()->make()->redirect('abc')->count(2)->lastHitAt(1751450400)->save();

    $found = Redirects::hits()->find('abc');

    expect($found)->toBeInstanceOf(RedirectHitContract::class)
        ->and($found->redirect())->toBe('abc')
        ->and($found->count())->toBe(2)
        ->and($found->lastHitAt())->toBe(1751450400);
});

it('can list and delete redirect hits via eloquent', function () {
    Redirects::hits()->make()->redirect('a')->count(1)->save();
    $b = Redirects::hits()->make()->redirect('b')->count(1);
    $b->save();

    expect(Redirects::hits()->all())->toHaveCount(2);

    $b->delete();

    expect(Redirects::hits()->all())->toHaveCount(1);
});

it('records a hit for a new redirect via eloquent', function () {
    Redirects::hits()->record('abc');

    $hit = Redirects::hits()->find('abc');

    expect($hit->count())->toBe(1)
        ->and($hit->lastHitAt())->not->toBeNull();
});

it('increments the count when recording a hit for an existing redirect via eloquent', function () {
    Redirects::hits()->make()->redirect('abc')->count(4)->save();

    Redirects::hits()->record('abc');

    expect(Redirects::hits()->find('abc')->count())->toBe(5);
});

it('queries redirect hits by redirect id via eloquent', function () {
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
