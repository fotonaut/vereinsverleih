<?php

namespace App\Services;

use App\Models\Item;
use App\Models\WaitlistEntry;
use App\Notifications\WaitlistAvailable;
use Illuminate\Support\Facades\Notification;

class WaitlistNotifier
{
    /**
     * Prüft die Warteliste eines Gegenstands und benachrichtigt, wessen Zeitraum jetzt frei ist.
     * Reihenfolge = Eintragungszeitpunkt. Wer bereits benachrichtigt wurde, "blockiert" dabei virtuell
     * den Bestand für später Eingetragene mit überlappendem Zeitraum (kein doppeltes "Es ist frei!").
     * Es wird nichts reserviert: Wer zuerst tatsächlich anfragt, bekommt den Gegenstand.
     *
     * @return int Anzahl benachrichtigter Einträge
     */
    public function check(Item $item): int
    {
        if (! $item->active) {
            return 0;
        }

        $entries = WaitlistEntry::where('item_id', $item->id)
            ->where('status', 'waiting')
            ->whereDate('end_date', '>=', today())
            ->orderBy('created_at')->orderBy('id')
            ->get();

        $notified = collect();

        foreach ($entries as $entry) {
            $start = $entry->start_date->toDateString();
            $end = $entry->end_date->toDateString();

            $virtualHold = $notified
                ->filter(fn (WaitlistEntry $n) => $n->start_date->lte($entry->end_date) && $n->end_date->gte($entry->start_date))
                ->sum('quantity');

            if ($item->availableQuantity($start, $end) - $virtualHold >= $entry->quantity) {
                Notification::route('mail', $entry->requester_email)->notify(new WaitlistAvailable($entry->setRelation('item', $item->loadMissing('club'))));
                $entry->update(['status' => 'notified', 'notified_at' => now()]);
                $notified->push($entry);
            }
        }

        return $notified->count();
    }
}
