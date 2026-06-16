<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('alertas:recalcular')->dailyAt('06:00');
Schedule::command('trial:expire-credits')->dailyAt('01:00');
Schedule::command('importacao:expirar-travadas')->everyMinute();
Schedule::command('assinatura:conceder-creditos')->dailyAt('03:30');
Schedule::command('monitoramento:executar-pendentes')->dailyAt('04:00')->withoutOverlapping();
