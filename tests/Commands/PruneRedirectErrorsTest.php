<?php

use Aerni\AdvancedSeo\Events\RedirectCreated;
use Aerni\AdvancedSeo\Events\RedirectSaved;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesRedirects;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class, EnablesRedirects::class);

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

it('does not age out errors when purge_after_days is false', function () {
    config(['advanced-seo.redirects.errors.purge_after_days' => false]);

    Redirect::errors()->make()->url('/old')->site('default')->count(1)
        ->lastSeenAt(Carbon::now()->subDays(40)->timestamp)->save();

    $this->artisan('seo:prune-redirect-errors')->assertExitCode(0);

    expect(Redirect::errors()->findByUrl('/old', 'default'))->not->toBeNull();
});

it('does not cap records when max_records is false', function () {
    config(['advanced-seo.redirects.errors.max_records' => false]);

    Redirect::errors()->make()->url('/a')->site('default')->count(1)->lastSeenAt(now()->timestamp)->save();
    Redirect::errors()->make()->url('/b')->site('default')->count(1)->lastSeenAt(now()->timestamp)->save();

    $this->artisan('seo:prune-redirect-errors')->assertExitCode(0);

    expect(Redirect::errors()->all())->toHaveCount(2);
});

it('deletes errors covered by enabled redirects', function () {
    Event::fake([RedirectCreated::class, RedirectSaved::class]);

    Redirect::errors()->make()->url('/old')->site('default')->count(1)->lastSeenAt(now()->timestamp)->save();
    Redirect::make()->source('/old')->destination('/new')->site('default')->save();

    $this->artisan('seo:prune-redirect-errors')->assertExitCode(0);

    expect(Redirect::errors()->findByUrl('/old', 'default'))->toBeNull();
});

it('ignores enabled redirects for unknown sites', function () {
    Event::fake([RedirectCreated::class, RedirectSaved::class]);

    Redirect::make()->source('/old')->destination('/new')->site('unknown')->save();

    $this->artisan('seo:prune-redirect-errors')->assertExitCode(0);
});

it('prunes after the redirect error lock is released', function () {
    config(['advanced-seo.redirects.errors.purge_after_days' => 30]);

    Redirect::errors()->make()->url('/old')->site('default')->count(1)
        ->lastSeenAt(Carbon::now()->subDays(40)->timestamp)->save();

    Sleep::fake(syncWithCarbon: true);

    try {
        $lock = Cache::lock('advanced-seo::redirect-error', 60);
        expect($lock->get())->toBeTrue();

        Sleep::whenFakingSleep(fn () => $lock->forceRelease());

        $this->artisan('seo:prune-redirect-errors')
            ->doesntExpectOutputToContain('write lock stayed busy')
            ->assertExitCode(0);

        expect(Redirect::errors()->findByUrl('/old', 'default'))->toBeNull();
    } finally {
        Sleep::fake(false);
        Carbon::setTestNow();
    }
});

it('warns and exits cleanly when the redirect error lock stays busy', function () {
    config(['advanced-seo.redirects.errors.purge_after_days' => 30]);

    Redirect::errors()->make()->url('/old')->site('default')->count(1)
        ->lastSeenAt(Carbon::now()->subDays(40)->timestamp)->save();

    $warnings = [];

    Log::listen(function (MessageLogged $event) use (&$warnings) {
        if ($event->level === 'warning') {
            $warnings[] = $event->message;
        }
    });

    Sleep::fake(syncWithCarbon: true);

    try {
        $lock = Cache::lock('advanced-seo::redirect-error', 120);
        expect($lock->get())->toBeTrue();

        $this->artisan('seo:prune-redirect-errors')
            ->expectsOutputToContain('Redirect error pruning did not finish because the write lock stayed busy. It will run again on the next schedule.')
            ->assertExitCode(0);

        expect(Redirect::errors()->findByUrl('/old', 'default'))->not->toBeNull()
            ->and($warnings)->toContain('Redirect error pruning did not finish because the write lock stayed busy. It will run again on the next schedule.');
    } finally {
        Sleep::fake(false);
        Carbon::setTestNow();
    }
});
