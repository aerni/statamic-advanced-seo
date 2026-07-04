<?php

use Aerni\AdvancedSeo\Facades\Redirect;
use Illuminate\Support\Carbon;

it('deletes errors older than the retention window', function () {
    config(['advanced-seo.redirects.errors.purge_after_days' => 30]);

    Redirect::errors()->make()->url('/old')->site('default')->count(1)
        ->lastSeenAt(Carbon::now()->subDays(40)->timestamp)->save();
    Redirect::errors()->make()->url('/fresh')->site('default')->count(1)
        ->lastSeenAt(Carbon::now()->subDays(2)->timestamp)->save();

    $this->artisan('seo:prune-redirect-errors')->assertExitCode(0);

    expect(Redirect::errors()->findByUrl('/old', 'default'))->toBeNull()
        ->and(Redirect::errors()->findByUrl('/fresh', 'default'))->not->toBeNull();
});

it('enforces the max_records cap after age-out', function () {
    config([
        'advanced-seo.redirects.errors.purge_after_days' => 30,
        'advanced-seo.redirects.errors.max_records' => 1,
    ]);

    Redirect::errors()->make()->url('/keep')->site('default')->count(9)
        ->lastSeenAt(Carbon::now()->timestamp)->save();
    Redirect::errors()->make()->url('/drop')->site('default')->count(1)
        ->lastSeenAt(Carbon::now()->timestamp)->save();

    $this->artisan('seo:prune-redirect-errors')->assertExitCode(0);

    expect(Redirect::errors()->all())->toHaveCount(1)
        ->and(Redirect::errors()->findByUrl('/keep', 'default'))->not->toBeNull();
});
