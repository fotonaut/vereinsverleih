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

        return (new MailMessage)
            ->subject('Neue Ausleihanfrage: '.$r->item->name)
            ->greeting('Neue Anfrage für „'.$r->item->name.'“')
            ->line($r->requester_name.' ('.($r->requester_type === 'club' ? 'Verein' : 'Privatperson').') möchte '.$r->quantity.' Stück vom '.$r->start_date->format('d.m.Y').' bis '.$r->end_date->format('d.m.Y').' ausleihen.')
            ->line($r->message ? 'Nachricht: '.$r->message : 'Keine Nachricht.')
            ->action('Anfrage bearbeiten', route('manage.incoming.index'));
    }
}
