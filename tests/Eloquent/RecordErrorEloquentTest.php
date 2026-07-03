<?php

use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;
use Illuminate\Support\Carbon;

uses(UseEloquentDriver::class);

it('evicts the lowest-count error via query when recording at capacity', function () {
    config(['advanced-seo.redirects.errors.max_records' => 2]);

    Redirect::errors()->make()->url('/keep')->site('default')->count(10)->lastSeenAt(Carbon::parse('2026-07-02 10:00:00')->timestamp)->save();
    Redirect::errors()->make()->url('/drop')->site('default')->count(1)->lastSeenAt(Carbon::parse('2026-07-02 09:00:00')->timestamp)->save();

    Redirect::errors()->record('/new', 'default');

    expect(Redirect::errors()->findByUrl('/drop', 'default'))->toBeNull()
        ->and(Redirect::errors()->findByUrl('/keep', 'default'))->not->toBeNull()
        ->and(Redirect::errors()->findByUrl('/new', 'default'))->not->toBeNull();
});
