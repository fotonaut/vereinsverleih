<?php

namespace App\Console\Commands;

use App\Enums\LoanStatus;
use App\Models\LoanRequest;
use App\Notifications\LoanReturnReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendReturnReminders extends Command
{
    protected $signature = 'loans:send-reminders';

    protected $description = 'Erinnert Ausleihende per E-Mail, wenn der Zeitraum morgen (oder heute) endet';

    public function handle(): int
    {
        $loans = LoanRequest::query()
            ->whereIn('status', [LoanStatus::Approved->value, LoanStatus::PickedUp->value])
            ->whereNull('reminded_at')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '<=', today()->addDay())
            ->whereDate('end_date', '>=', today())
            ->with('item.club')
            ->get();

        foreach ($loans as $loan) {
            Notification::route('mail', $loan->requester_email)->notify(new LoanReturnReminder($loan));
            $loan->forceFill(['reminded_at' => now()])->save();
        }

        $this->info($loans->count().' Erinnerung(en) versendet.');

        return self::SUCCESS;
    }
}
