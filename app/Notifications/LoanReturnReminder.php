<?php

namespace App\Notifications;

use App\Models\LoanRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanReturnReminder extends Notification implements ShouldQueue
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
        $when = $r->end_date->isToday() ? 'heute' : 'morgen';

        return (new MailMessage)
            ->subject('Erinnerung: Rückgabe von „'.$r->item->name.'“ '.$when)
            ->greeting('Hallo '.$r->requester_name.',')
            ->line('der Ausleihzeitraum für „'.$r->item->name.'“ ('.$r->quantity.'×) endet '.$when.', am '.$r->end_date->format('d.m.Y').'.')
            ->line('Bitte gib den Gegenstand rechtzeitig zurück. Abholort / Rückgabe: '.($r->item->location ?: 'bitte direkt mit dem Verein abstimmen').'.')
            ->line('Kontakt des Vereins '.$r->item->club->name.': '.$r->item->club->email)
            ->action('Anfrage ansehen', route('requests.show', $r->token));
    }
}
