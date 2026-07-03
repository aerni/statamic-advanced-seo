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
        ->lastHitAt('2026-07-02 10:00:00');

    expect($hit->fileData())->toBe([
        'count' => 5,
        'last_hit_at' => '2026-07-02 10:00:00',
    ]);
});

it('builds its path from the redirect-hits store directory and id', function () {
    expect((new RedirectHit)->redirect('abc')->path())->toEndWith('redirect-hits/abc.yaml');
});
