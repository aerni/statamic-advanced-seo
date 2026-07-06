<?php

use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;
use Illuminate\Support\Carbon;

uses(UseEloquentDriver::class);

it('creates an error with count 1 on the first record', function () {
    Redirect::errors()->record('/missing', 'default');

    $error = Redirect::errors()->findByUrl('/missing', 'default');

    expect($error)->not->toBeNull()
        ->and($error->count())->toBe(1)
        ->and($error->firstSeenAt())->not->toBeNull()
        ->and($error->lastSeenAt())->not->toBeNull();
});

it('increments an existing error instead of creating a duplicate', function () {
    Redirect::errors()->record('/missing', 'default');
    Redirect::errors()->record('/missing', 'default');

    expect(Redirect::errors()->findByUrl('/missing', 'default')->count())->toBe(2)
        ->and(Redirect::errors()->all())->toHaveCount(1);
});

it('records and dedups urls longer than 255 characters via the url hash', function () {
    $long = '/'.str_repeat('a', 300);
    $other = '/'.str_repeat('b', 300);

    Redirect::errors()->record($long, 'default');
    Redirect::errors()->record($long, 'default');
    Redirect::errors()->record($other, 'default');

    expect(Redirect::errors()->findByUrl($long, 'default')->count())->toBe(2)
        ->and(Redirect::errors()->findByUrl($other, 'default')->count())->toBe(1)
        ->and(Redirect::errors()->all())->toHaveCount(2);
});

it('evicts the lowest-count error via query when recording at capacity', function () {
    config(['advanced-seo.redirects.errors.max_records' => 2]);

    Redirect::errors()->make()->url('/keep')->site('default')->count(10)->lastSeenAt(Carbon::parse('2026-07-02 10:00:00')->timestamp)->save();
    Redirect::errors()->make()->url('/drop')->site('default')->count(1)->lastSeenAt(Carbon::parse('2026-07-02 09:00:00')->timestamp)->save();

    Redirect::errors()->record('/new', 'default');

    expect(Redirect::errors()->findByUrl('/drop', 'default'))->toBeNull()
        ->and(Redirect::errors()->findByUrl('/keep', 'default'))->not->toBeNull()
        ->and(Redirect::errors()->findByUrl('/new', 'default'))->not->toBeNull();
});
