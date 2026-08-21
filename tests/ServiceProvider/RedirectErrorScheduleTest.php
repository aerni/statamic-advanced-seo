<?php

use Aerni\AdvancedSeo\ServiceProvider;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesRedirects;
use Illuminate\Console\Scheduling\Schedule;

uses(EnablesRedirects::class);

function scheduledPruneEvent()
{
    return collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'seo:prune-redirect-errors'));
}

function invokeSchedule(): Schedule
{
    $schedule = new Schedule;

    $provider = app()->make(ServiceProvider::class, ['app' => app()]);

    (fn () => $this->schedule($schedule))->call($provider);

    return $schedule;
}

it('schedules the redirect error prune command to run daily', function () {
    $event = scheduledPruneEvent();

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 0 * * *');
});

it('does not schedule the redirect error prune command on the free edition', function () {
    useFreeEdition();
    flushBlink();

    expect(invokeSchedule()->events())->toBeEmpty();
});

it('does not schedule the redirect error prune command when redirect errors are disabled', function () {
    config(['advanced-seo.redirects.errors.enabled' => false]);

    expect(invokeSchedule()->events())->toBeEmpty();
});
