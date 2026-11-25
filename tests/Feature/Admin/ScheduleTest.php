<?php

use Illuminate\Console\Scheduling\Schedule;

it('schedules the queue, scheduled publishing, backups and clean-up', function () {
    $commands = collect(app(Schedule::class)->events())->map(fn ($event) => $event->command)->implode("\n");

    expect($commands)
        ->toContain('queue:work --stop-when-empty')
        ->toContain('catalog:publish-scheduled')
        ->toContain('backup:run')
        ->toContain('backup:clean')
        ->toContain('backup:monitor')
        ->toContain('queue:prune-failed')
        ->toContain('sanctum:prune-expired');
});
