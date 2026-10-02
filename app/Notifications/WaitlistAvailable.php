<?php

namespace App\Notifications;

use App\Models\WaitlistEntry;
use App\Support\LoanSeries;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WaitlistAvailable extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public WaitlistEntry $entry) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $e = $this->entry;

        $mail = (new MailMessage)
            ->subject('Jetzt frei: '.$e->item->name)
            ->greeting('Gute Nachrichten, '.$e->requester_name.'!');

        if ($e->isSeries()) {
            $mail->line('„'.$e->item->name.'“ ('.$e->quantity.'×) ist jetzt für **alle '.$e->repeat_count.' Termine** deiner Serie ('.LoanSeries::INTERVALS[$e->repeat].') verfügbar:');
            foreach ($e->occurrences() as $o) {
                $mail->line('• '.Carbon::parse($o['start'])->format('d.m.Y').' – '.Carbon::parse($o['end'])->format('d.m.Y'));
            }
        } else {
            $mail->line('„'.$e->item->name.'“ ('.$e->quantity.'×) ist im Zeitraum '.$e->start_date->format('d.m.Y').' – '.$e->end_date->format('d.m.Y').' jetzt verfügbar.');
        }

        return $mail
            ->line('Es gilt: Wer zuerst anfragt, bekommt den Gegenstand. Es wurde nichts für dich reserviert, also am besten gleich anfragen.')
            ->action('Jetzt anfragen', route('catalog.show', $e->item_id))
            ->line('Du stehst danach nicht mehr auf der Warteliste.');
    }
}
