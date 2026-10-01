<?php

namespace App\Notifications;

use App\Models\WaitlistEntry;
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

        return (new MailMessage)
            ->subject('Jetzt frei: '.$e->item->name)
            ->greeting('Gute Nachrichten, '.$e->requester_name.'!')
            ->line('„'.$e->item->name.'“ ('.$e->quantity.'×) ist im Zeitraum '.$e->start_date->format('d.m.Y').' – '.$e->end_date->format('d.m.Y').' jetzt verfügbar.')
            ->line('Es gilt: Wer zuerst anfragt, bekommt den Gegenstand. Es wurde nichts für dich reserviert, also am besten gleich anfragen.')
            ->action('Jetzt anfragen', route('catalog.show', $e->item_id))
            ->line('Du stehst danach nicht mehr auf der Warteliste.');
    }
}
