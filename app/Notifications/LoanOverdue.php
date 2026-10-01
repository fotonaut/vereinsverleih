<?php

namespace App\Notifications;

use App\Models\LoanRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Mahnung: geht an die Ausleihenden (forOwner=false) bzw. als Info an den verleihenden Verein (forOwner=true). */
class LoanOverdue extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LoanRequest $request, public bool $forOwner = false) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $r = $this->request;
        $days = (int) $r->end_date->diffInDays(today());
        $dayText = $days === 1 ? '1 Tag' : $days.' Tagen';

        if ($this->forOwner) {
            return (new MailMessage)
                ->subject('Überfällig: „'.$r->item->name.'“ von '.$r->requester_name)
                ->greeting('Rückgabe überfällig')
                ->line('„'.$r->item->name.'“ ('.$r->quantity.'×) war an '.$r->requester_name.' bis '.$r->end_date->format('d.m.Y').' verliehen und ist seit '.$dayText.' überfällig.')
                ->line('Wir haben die Ausleihenden ('.$r->requester_email.') per E-Mail erinnert. Markiere den Gegenstand nach der Rückgabe als „zurückgegeben“.')
                ->action('Anfragen ansehen', route('manage.incoming.index'));
        }

        return (new MailMessage)
            ->subject('Rückgabe überfällig: „'.$r->item->name.'“')
            ->greeting('Hallo '.$r->requester_name.',')
            ->line('der Ausleihzeitraum für „'.$r->item->name.'“ ('.$r->quantity.'×) ist am '.$r->end_date->format('d.m.Y').' abgelaufen, die Rückgabe ist seit '.$dayText.' überfällig.')
            ->line('Bitte bring den Gegenstand so schnell wie möglich zurück oder sprich eine Verlängerung mit dem Verein ab.')
            ->line('Kontakt des Vereins '.$r->item->club->name.': '.$r->item->club->email.' · Rückgabeort: '.($r->item->location ?: 'nach Absprache'))
            ->action('Anfrage ansehen', route('requests.show', $r->token));
    }
}
