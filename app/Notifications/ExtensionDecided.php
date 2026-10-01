<?php

namespace App\Notifications;

use App\Models\LoanExtension;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExtensionDecided extends Notification implements ShouldQueue
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
        $approved = $e->status === 'approved';

        $mail = (new MailMessage)
            ->subject('Verlängerung '.($approved ? 'genehmigt' : 'abgelehnt').': '.$r->item->name)
            ->greeting('Hallo '.$r->requester_name.',')
            ->line($approved
                ? 'deine Verlängerung für „'.$r->item->name.'“ wurde genehmigt. Neues Rückgabedatum: **'.$e->requested_end_date->format('d.m.Y').'**.'
                : 'deine Verlängerung für „'.$r->item->name.'“ wurde leider abgelehnt. Es bleibt beim Rückgabedatum '.$e->previous_end_date->format('d.m.Y').'.');

        if ($e->decision_note) {
            $mail->line('Hinweis vom Verein: '.$e->decision_note);
        }

        return $mail->action('Anfrage ansehen', route('requests.show', $r->token));
    }
}
