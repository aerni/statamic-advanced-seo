<?php

use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Jobs\RecordRedirectErrorJob;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

it('creates an error on first record and seeds first_seen_at', function () {
    Redirect::errors()->record('/missing', 'default');

    $error = Redirect::errors()->findByUrl('/missing', 'default');

    expect($error->count())->toBe(1)
        ->and($error->firstSeenAt())->not->toBeNull()
        ->and($error->lastSeenAt())->not->toBeNull();
});

it('increments an existing error and preserves first_seen_at', function () {
    Redirect::errors()->make()->url('/missing')->site('default')->count(3)
        ->firstSeenAt(Carbon::parse('2026-07-01 09:00:00')->timestamp)->save();

    Redirect::errors()->record('/missing', 'default');

    $error = Redirect::errors()->findByUrl('/missing', 'default');

    expect($error->count())->toBe(4)
        ->and($error->firstSeenAt())->toBe(Carbon::parse('2026-07-01 09:00:00')->timestamp);
});

it('evicts the lowest-count error when recording at capacity', function () {
    config(['advanced-seo.redirects.errors.max_records' => 2]);

    Redirect::errors()->make()->url('/keep')->site('default')->count(10)->lastSeenAt(Carbon::parse('2026-07-02 10:00:00')->timestamp)->save();
    Redirect::errors()->make()->url('/drop')->site('default')->count(1)->lastSeenAt(Carbon::parse('2026-07-02 09:00:00')->timestamp)->save();

    Redirect::errors()->record('/new', 'default');

    expect(Redirect::errors()->findByUrl('/drop', 'default'))->toBeNull()
        ->and(Redirect::errors()->findByUrl('/keep', 'default'))->not->toBeNull()
        ->and(Redirect::errors()->findByUrl('/new', 'default'))->not->toBeNull();
});

it('keeps at least one error when max_records is zero', function () {
    config(['advanced-seo.redirects.errors.max_records' => 0]);

    Redirect::errors()->record('/a', 'default');
    Redirect::errors()->record('/b', 'default');
    Redirect::errors()->record('/c', 'default');

    expect(Redirect::errors()->all())->toHaveCount(1)
        ->and(Redirect::errors()->findByUrl('/c', 'default'))->not->toBeNull();
});

it('does not evict when max_records is false', function () {
    config(['advanced-seo.redirects.errors.max_records' => false]);

    Redirect::errors()->make()->url('/a')->site('default')->count(1)->save();
    Redirect::errors()->make()->url('/b')->site('default')->count(1)->save();

    Redirect::errors()->record('/c', 'default');

    expect(Redirect::errors()->all())->toHaveCount(3);
});

it('records different urls behind the same global lock', function () {
    Cache::spy();

    Redirect::errors()->record('/one', 'default');
    Redirect::errors()->record('/two', 'default');

    Cache::shouldHaveReceived('lock')
        ->with('advanced-seo::redirect-error', 60)
        ->twice();
});

it('records via the queued job', function () {
    (new RecordRedirectErrorJob('/missing', 'default'))->handle();

    expect(Redirect::errors()->findByUrl('/missing', 'default')->count())->toBe(1);
});

it('is unique per site and url to collapse duplicate recordings', function () {
    $job = new RecordRedirectErrorJob('/missing', 'default');

    expect($job)->toBeInstanceOf(ShouldBeUnique::class)
        ->and($job->uniqueId())->toBe('default:/missing');
});
