<?php

namespace App\Notifications;

use App\Models\LoanRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanRequestReceived extends Notification implements ShouldQueue
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

        $series = $r->seriesLoans();
        $who = $r->requester_name.' ('.($r->requester_type === 'club' ? 'Verein' : 'Privatperson').')';

        $mail = (new MailMessage)
            ->subject(($series->count() > 1 ? 'Neue Serien-Anfrage: ' : 'Neue Ausleihanfrage: ').$r->item->name)
            ->greeting('Neue Anfrage für „'.$r->item->name.'“');

        if ($series->count() > 1) {
            $mail->line($who.' möchte '.$r->quantity.' Stück in einer Serie mit '.$series->count().' Terminen ausleihen:');
            foreach ($series as $l) {
                $mail->line('• '.$l->start_date->format('d.m.Y').' – '.$l->end_date->format('d.m.Y'));
            }
        } else {
            $mail->line($who.' möchte '.$r->quantity.' Stück vom '.$r->start_date->format('d.m.Y').' bis '.$r->end_date->format('d.m.Y').' ausleihen.');
        }

        return $mail
            ->line($r->message ? 'Nachricht: '.$r->message : 'Keine Nachricht.')
            ->action('Anfrage bearbeiten', route('manage.incoming.index'));
    }
}
