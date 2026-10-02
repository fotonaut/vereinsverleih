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

// Datensparsamkeit: Gast-Anfragen, die nie per E-Mail bestätigt wurden, werden nach Ablauf der Frist gelöscht.
Schedule::call(function () {
    App\Models\LoanRequest::where('status', 'unverified')
        ->where('created_at', '<', now()->subDays(config('imprint.unverified_retention_days')))
        ->delete();
})->daily()->name('prune-unverified-requests');

// Erinnerung an die Rückgabe (Zeitraum endet morgen); der 5-Minuten-Cron löst das ab 08:00 aus.
Schedule::command('loans:send-reminders')->dailyAt('08:00');

// Mahnung bei überfälliger Rückgabe (max. 3×, Abstand 3 Tage).
Schedule::command('loans:send-overdue')->dailyAt('08:10');

// Warteliste aufräumen: abgelaufene Zeiträume schließen, unbestätigte Einträge nach Frist löschen.
Schedule::call(function () {
    App\Models\WaitlistEntry::whereIn('status', ['unverified', 'waiting'])
        ->stale()
        ->update(['status' => 'expired']);

    App\Models\WaitlistEntry::where('status', 'unverified')
        ->where('created_at', '<', now()->subDays(config('imprint.unverified_retention_days')))
        ->delete();
})->daily()->name('cleanup-waitlist');
