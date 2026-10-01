<?php

namespace App\Notifications;

use App\Models\LoanExtension;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExtensionRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LoanExtension $extension) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $e = $this->extension;
        $r = $e->loanRequest;

        return (new MailMessage)
            ->subject('Verlängerungsanfrage: '.$r->item->name)
            ->greeting('Verlängerung für „'.$r->item->name.'“')
            ->line($r->requester_name.' möchte den Zeitraum von '.$e->previous_end_date->format('d.m.Y').' auf '.$e->requested_end_date->format('d.m.Y').' verlängern.')
            ->line($e->message ? 'Nachricht: '.$e->message : 'Keine Nachricht.')
            ->action('Anfrage bearbeiten', route('manage.incoming.index'));
    }
}
