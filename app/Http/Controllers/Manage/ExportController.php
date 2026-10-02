<?php

namespace App\Http\Controllers\Manage;

use App\Enums\LoanStatus;
use App\Http\Controllers\Controller;
use App\Models\LoanRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    private const HEADERS = [
        'ID', 'Gegenstand', 'Verleihender Verein', 'Anfragende(r)', 'Typ', 'E-Mail', 'Telefon', 'Menge',
        'Von', 'Bis', 'Status', 'Nachricht', 'Hinweis des Vereins', 'Angefragt am',
        'Zurückgegeben am', 'Zustand bei Rückgabe', 'Rückgabe-Notiz', 'Kaution zurückgegeben',
    ];

    /** CSV-Export der Ausleihen des eigenen Vereins (eingehend = wir verleihen, ausgehend = wir leihen). */
    public function loans(Request $request): StreamedResponse
    {
        $club = $request->user()->club_id ?: abort(403);

        $data = $request->validate([
            'direction' => ['nullable', Rule::in(['incoming', 'outgoing'])],
            'status' => ['nullable', Rule::enum(LoanStatus::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $direction = $data['direction'] ?? 'incoming';

        $query = LoanRequest::query()
            ->with('item.club')
            ->when($direction === 'incoming',
                fn ($q) => $q->whereHas('item', fn ($i) => $i->where('club_id', $club))
                    ->where('status', '!=', LoanStatus::Unverified->value),
                fn ($q) => $q->where('requester_club_id', $club))
            ->when($data['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            // Zeitraum: alles, was sich mit [from, to] überschneidet
            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('end_date', '>=', $d))
            ->when($data['to'] ?? null, fn ($q, $d) => $q->whereDate('start_date', '<=', $d))
            ->orderBy('start_date')->orderBy('id');

        $filename = 'ausleihen-'.($direction === 'incoming' ? 'verliehen' : 'geliehen').'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM, damit Excel UTF-8 (Umlaute) erkennt
            fputcsv($out, self::HEADERS, ';');

            $query->chunkById(200, function ($loans) use ($out) {
                foreach ($loans as $l) {
                    fputcsv($out, array_map([self::class, 'safe'], [
                        $l->id,
                        $l->item->name,
                        $l->item->club->name,
                        $l->requester_name,
                        $l->requester_type === 'club' ? 'Verein' : 'Privatperson',
                        $l->requester_email,
                        $l->requester_phone,
                        $l->quantity,
                        $l->start_date->format('d.m.Y'),
                        $l->end_date->format('d.m.Y'),
                        $l->status->label(),
                        $l->message,
                        $l->decision_note,
                        $l->created_at->format('d.m.Y H:i'),
                        $l->returned_at?->format('d.m.Y H:i'),
                        $l->return_condition?->label(),
                        $l->return_note,
                        $l->status->value === 'returned' ? ($l->deposit_returned ? 'Ja' : 'Nein') : null,
                    ]), ';');
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Schutz vor CSV-/Formel-Injection: Zellen, die mit = + - @ oder Tab/CR beginnen, werden entschärft. */
    public static function safe(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
