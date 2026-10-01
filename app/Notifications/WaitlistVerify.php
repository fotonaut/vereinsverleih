<?php

namespace App\Notifications;

use App\Models\WaitlistEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WaitlistVerify extends Notification implements ShouldQueue
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
            ->subject('Bitte bestätige deinen Wartelisten-Eintrag: '.$e->item->name)
            ->greeting('Hallo '.$e->requester_name.',')
            ->line('du möchtest auf die Warteliste für „'.$e->item->name.'“ ('.$e->quantity.'×, '.$e->start_date->format('d.m.Y').' – '.$e->end_date->format('d.m.Y').').')
            ->line('Bestätige bitte deine E-Mail-Adresse, damit wir dich benachrichtigen können, sobald der Gegenstand frei wird.')
            ->action('Eintrag bestätigen', route('waitlist.verify', $e->token))
            ->line('Wenn du das nicht warst, ignoriere diese E-Mail einfach.');
    }
}
