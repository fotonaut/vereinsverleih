<?php

namespace App\Notifications;

use App\Models\LoanRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanReturned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LoanRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $r = $this->request;

        $mail = (new MailMessage)
            ->subject('Rückgabe bestätigt: '.$r->item->name)
            ->greeting('Hallo '.$r->requester_name.',')
            ->line('der Verein '.$r->item->club->name.' hat die Rückgabe von „'.$r->item->name.'“ ('.$r->quantity.'×) bestätigt. Danke!')
            ->line('Zustand bei Rückgabe: **'.($r->return_condition?->label() ?? 'Einwandfrei').'**');

        if ($r->return_note) {
            $mail->line('Notiz des Vereins: '.$r->return_note);
        }
        if ($r->item->deposit_cents) {
            $mail->line($r->deposit_returned
                ? 'Deine Kaution ('.number_format($r->item->deposit_cents / 100, 2, ',', '.').' €) wurde zurückgegeben.'
                : 'Zur Kaution ('.number_format($r->item->deposit_cents / 100, 2, ',', '.').' €): Der Verein meldet sich bei dir, falls noch etwas zu klären ist.');
        }

        return $mail->action('Anfrage ansehen', route('requests.show', $r->token));
    }
}
