<?php

use Illuminate\Console\Scheduling\Schedule;

function scheduledPruneEvent()
{
    return collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'seo:prune-redirect-errors'));
}

it('schedules the redirect error prune command to run daily', function () {
    $event = scheduledPruneEvent();

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 0 * * *');
});
