<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Comprobación diaria del expediente (§22). Necesita el cron de Laravel en el servidor
// (ver deploy/crontab); cada fallo queda en el historial de su obra.
Schedule::command('arthub:comprobar-integridad')
    ->dailyAt('03:00')
    ->timezone('America/Lima')
    ->withoutOverlapping();
