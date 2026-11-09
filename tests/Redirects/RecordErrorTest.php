<?php

use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Jobs\RecordRedirectErrorJob;
use Illuminate\Bus\UniqueLock;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Sleep;
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

it('releases the job when the redirect error lock is held and records once the lock is free', function () {
    bootRedirectErrorQueue();
    Sleep::fake(syncWithCarbon: true);

    try {
        $lock = Cache::lock('advanced-seo::redirect-error', 60);
        expect($lock->get())->toBeTrue();

        RecordRedirectErrorJob::dispatch('/secrets.yml', 'default');

        workNextRedirectErrorJob();

        expect(DB::table('failed_jobs')->count())->toBe(0)
            ->and(DB::table('jobs')->count())->toBe(1);

        $delay = DB::table('jobs')->value('available_at') - Carbon::now()->getTimestamp();

        expect($delay)->toBeGreaterThanOrEqual(5)
            ->and($delay)->toBeLessThanOrEqual(15)
            ->and(Redirect::errors()->findByUrl('/secrets.yml', 'default'))->toBeNull()
            ->and(Cache::lock(UniqueLock::getKey(new RecordRedirectErrorJob('/secrets.yml', 'default')))->get())->toBeFalse();

        $lock->forceRelease();

        Carbon::setTestNow(Carbon::now()->addSeconds(16));
        workNextRedirectErrorJob();

        expect(Redirect::errors()->findByUrl('/secrets.yml', 'default')->count())->toBe(1)
            ->and(DB::table('failed_jobs')->count())->toBe(0)
            ->and(DB::table('jobs')->count())->toBe(0);
    } finally {
        Sleep::fake(false);
        Carbon::setTestNow();
    }
});

it('gives up without failing when the redirect error lock stays busy', function () {
    bootRedirectErrorQueue();
    Sleep::fake(syncWithCarbon: true);

    try {
        $tries = (new RecordRedirectErrorJob('/telescope/requests', 'default'))->tries;

        $lock = Cache::lock('advanced-seo::redirect-error', 120);
        expect($lock->get())->toBeTrue();

        RecordRedirectErrorJob::dispatch('/telescope/requests', 'default');

        for ($attempt = 0; $attempt < $tries; $attempt++) {
            Carbon::setTestNow(Carbon::now()->addSeconds(16));
            workNextRedirectErrorJob();
        }

        expect($tries)->toBeGreaterThan(1)
            ->and(DB::table('jobs')->count())->toBe(0)
            ->and(DB::table('failed_jobs')->count())->toBe(0)
            ->and(Redirect::errors()->findByUrl('/telescope/requests', 'default'))->toBeNull();
    } finally {
        Sleep::fake(false);
        Carbon::setTestNow();
    }
});

function bootRedirectErrorQueue(): void
{
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'queue.connections.database' => [
            'driver' => 'database',
            'connection' => 'sqlite',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
        ],
        'queue.failed.driver' => 'database-uuids',
        'queue.failed.database' => 'sqlite',
        'queue.failed.table' => 'failed_jobs',
    ]);

    DB::purge('sqlite');
    app('queue')->setDefaultDriver('database');

    Schema::create('jobs', function ($table) {
        $table->bigIncrements('id');
        $table->string('queue')->index();
        $table->longText('payload');
        $table->unsignedSmallInteger('attempts');
        $table->unsignedInteger('reserved_at')->nullable();
        $table->unsignedInteger('available_at');
        $table->unsignedInteger('created_at');
    });

    Schema::create('failed_jobs', function ($table) {
        $table->id();
        $table->string('uuid')->unique();
        $table->string('connection');
        $table->string('queue');
        $table->longText('payload');
        $table->longText('exception');
        $table->timestamp('failed_at')->useCurrent();
    });
}

function workNextRedirectErrorJob(): void
{
    app('queue.worker')->runNextJob('database', 'default', new WorkerOptions(sleep: 0, maxTries: 5));
}
