<?php

use Aerni\AdvancedSeo\Redirects\RedirectHit;

it('exposes fluent accessors with sensible defaults', function () {
    $hit = (new RedirectHit)->redirect('abc');

    expect($hit->redirect())->toBe('abc')
        ->and($hit->count())->toBe(0)
        ->and($hit->lastHitAt())->toBeNull();
});

it('derives its id from the redirect', function () {
    expect((new RedirectHit)->redirect('abc')->id())->toBe('abc');
});

it('serializes only its fields to file data', function () {
    $hit = (new RedirectHit)
        ->redirect('abc')
        ->count(5)
        ->lastHitAt(1751450400);

    expect($hit->fileData())->toBe([
        'count' => 5,
        'last_hit_at' => 1751450400,
    ]);
});

it('builds its path from the redirect-hits store directory and id', function () {
    expect((new RedirectHit)->redirect('abc')->path())->toEndWith('redirect-hits/abc.yaml');
});

it('exposes last hit as an iso 8601 string and null when never hit', function () {
    expect((new RedirectHit)->redirect('abc')->lastHitAt(1751450400)->lastHitAtIso())
        ->toBe('2025-07-02T10:00:00+00:00')
        ->and((new RedirectHit)->redirect('abc')->lastHitAtIso())->toBeNull();
});
