<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClubTestMail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $clubName) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Test: Benachrichtigungen von '.$this->clubName)
            ->greeting('Test erfolgreich')
            ->line('Diese Test-Mail zeigt, dass Benachrichtigungen für „'.$this->clubName.'“ an diese Adresse zugestellt werden.')
            ->line('Du erhältst sie, weil sie als Empfänger für Vereins-Benachrichtigungen eingetragen ist.')
            ->action('Einstellungen öffnen', route('manage.notifications.edit'));
    }
}
