<?php

namespace App\Notifications;

use App\Models\LoanRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanRequestDecided extends Notification implements ShouldQueue
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
        $mail = (new MailMessage)
            ->subject('Deine Anfrage für „'.$r->item->name.'“: '.$r->status->label())
            ->greeting('Hallo '.$r->requester_name.',')
            ->line('der Status deiner Anfrage hat sich geändert: **'.$r->status->label().'**.');

        if ($r->decision_note) {
            $mail->line('Hinweis vom Verein: '.$r->decision_note);
        }
        if ($r->status->value === 'approved') {
            $mail->line('Abholort: '.($r->item->location ?: 'bitte direkt mit dem Verein abstimmen').' — Kontakt: '.$r->item->club->email);
        }

        return $mail->action('Anfrage ansehen', route('requests.show', $r->token));
    }
}
