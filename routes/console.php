<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auf dem Webhosting läuft kein dauerhafter Queue-Worker: per Cron (alle 5 Min.) `schedule:run`,
// das hier die Warteschlange abarbeitet.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')->everyFiveMinutes()->withoutOverlapping();
