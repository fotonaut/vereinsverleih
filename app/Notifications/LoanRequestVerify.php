<?php

namespace App\Notifications;

use App\Models\LoanRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanRequestVerify extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LoanRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $series = $this->request->seriesLoans();
        $when = $series->count() > 1
            ? 'in einer Serie mit '.$series->count().' Terminen ('.$series->first()->start_date->format('d.m.Y').' bis '.$series->last()->end_date->format('d.m.Y').') angefragt'
            : 'vom '.$this->request->start_date->format('d.m.Y').' bis '.$this->request->end_date->format('d.m.Y').' angefragt';

        return (new MailMessage)
            ->subject('Bitte bestätige deine Anfrage: '.$this->request->item->name)
            ->greeting('Hallo '.$this->request->requester_name.',')
            ->line('du hast „'.$this->request->item->name.'“ '.$when.'.')
            ->line('Damit der Verein deine Anfrage sieht, bestätige bitte deine E-Mail-Adresse.')
            ->action('Anfrage bestätigen', route('requests.verify', $this->request->token))
            ->line('Wenn du die Anfrage nicht gestellt hast, ignoriere diese E-Mail einfach.');
    }
}
