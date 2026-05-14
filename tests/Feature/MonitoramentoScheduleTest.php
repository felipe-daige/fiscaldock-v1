<?php

use Illuminate\Console\Scheduling\Schedule;

it('agenda o command de monitoramento diariamente', function () {
    $schedule = app(Schedule::class);

    $eventos = collect($schedule->events())
        ->filter(fn ($e) => str_contains($e->command ?? '', 'monitoramento:executar-pendentes'));

    expect($eventos)->toHaveCount(1);
    expect($eventos->first()->expression)->toBe('0 4 * * *');
});
