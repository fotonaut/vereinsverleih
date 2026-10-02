<?php

namespace App\Notifications;

use App\Models\LoanRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Eine gebündelte Entscheidung (genehmigt/abgelehnt) für alle Termine einer Serie. */
class LoanSeriesDecided extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $seriesId, public string $status, public ?string $note = null) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $loans = LoanRequest::where('series_id', $this->seriesId)->with('item.club')->orderBy('start_date')->get();
        $first = $loans->first();
        $approved = $this->status === 'approved';

        $mail = (new MailMessage)
            ->subject('Serien-Anfrage '.($approved ? 'genehmigt' : 'abgelehnt').': '.$first->item->name)
            ->greeting('Hallo '.$first->requester_name.',')
            ->line('deine Serien-Anfrage für „'.$first->item->name.'“ ('.$loans->count().' Termine) wurde '.($approved ? '**genehmigt**' : '**abgelehnt**').':');

        foreach ($loans as $l) {
            $mail->line('• '.$l->start_date->format('d.m.Y').' – '.$l->end_date->format('d.m.Y'));
        }
        if ($this->note) {
            $mail->line('Hinweis vom Verein: '.$this->note);
        }
        if ($approved) {
            $mail->line('Abholort: '.($first->item->location ?: 'bitte direkt mit dem Verein abstimmen').' — Kontakt: '.$first->item->club->email);
        }

        return $mail->action('Anfrage ansehen', route('requests.show', $first->token));
    }
}
