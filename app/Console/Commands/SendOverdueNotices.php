<?php

namespace App\Console\Commands;

use App\Enums\LoanStatus;
use App\Models\LoanRequest;
use App\Notifications\LoanOverdue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendOverdueNotices extends Command
{
    /** Mahnungen insgesamt, danach ist es Sache der Vereine. */
    public const MAX_NOTICES = 3;

    /** Mindestabstand zwischen zwei Mahnungen in Tagen. */
    public const INTERVAL_DAYS = 3;

    protected $signature = 'loans:send-overdue';

    protected $description = 'Mahnt überfällige Rückgaben an (Ausleihende) und informiert den verleihenden Verein';

    public function handle(): int
    {
        $loans = LoanRequest::query()
            ->where('status', LoanStatus::PickedUp->value)
            ->whereDate('end_date', '<', today())
            ->where('overdue_count', '<', self::MAX_NOTICES)
            ->where(fn ($q) => $q->whereNull('overdue_notified_at')
                ->orWhere('overdue_notified_at', '<=', now()->subDays(self::INTERVAL_DAYS)->addMinutes(30)))
            ->with('item.club')
            ->get();

        foreach ($loans as $loan) {
            Notification::route('mail', $loan->requester_email)->notify(new LoanOverdue($loan));

            $loan->item->club->notifyContacts(new LoanOverdue($loan, forOwner: true), 'overdue');

            $loan->forceFill(['overdue_notified_at' => now(), 'overdue_count' => $loan->overdue_count + 1])->save();
        }

        $this->info($loans->count().' Mahnung(en) versendet.');

        return self::SUCCESS;
    }
}
