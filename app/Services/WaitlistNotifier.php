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

        // Abgelaufene Wünsche schließen, statt sie zu benachrichtigen
        WaitlistEntry::where('item_id', $item->id)->where('status', 'waiting')->stale()->update(['status' => 'expired']);

        $entries = WaitlistEntry::where('item_id', $item->id)
            ->where('status', 'waiting')
            ->orderBy('created_at')->orderBy('id')
            ->get();

        $notified = collect();

        foreach ($entries as $entry) {
            // Bei einer Serie müssen ALLE Termine frei sein (wie beim Buchen: alles oder nichts)
            $allFree = collect($entry->occurrences())->every(function (array $o) use ($item, $entry, $notified) {
                $virtualHold = $notified
                    ->filter(fn (WaitlistEntry $n) => collect($n->occurrences())
                        ->contains(fn (array $no) => $no['start'] <= $o['end'] && $no['end'] >= $o['start']))
                    ->sum('quantity');

                return $item->availableQuantity($o['start'], $o['end']) - $virtualHold >= $entry->quantity;
            });

            if ($allFree) {
                Notification::route('mail', $entry->requester_email)->notify(new WaitlistAvailable($entry->setRelation('item', $item->loadMissing('club'))));
                $entry->update(['status' => 'notified', 'notified_at' => now()]);
                $notified->push($entry);
            }
        }

        return $notified->count();
    }
}
