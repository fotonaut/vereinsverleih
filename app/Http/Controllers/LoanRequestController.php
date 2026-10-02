<?php

namespace App\Http\Controllers;

use App\Enums\LoanStatus;
use App\Http\Requests\StoreLoanRequest;
use App\Models\Item;
use App\Models\LoanRequest;
use App\Notifications\ExtensionRequested;
use App\Services\WaitlistNotifier;
use App\Notifications\LoanRequestReceived;
use App\Notifications\LoanRequestVerify;
use App\Support\LoanSeries;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LoanRequestController extends Controller
{
    private const CANCELLABLE = [LoanStatus::Unverified, LoanStatus::Pending, LoanStatus::Approved];

    public function store(StoreLoanRequest $request, Item $item): RedirectResponse
    {
        abort_unless($item->active, 404);

        $user = $request->user();
        $type = $user?->club_id ? 'club' : 'private';

        if ($user && ! $user->club_id) {
            abort(403);
        }
        if ($user?->club_id === $item->club_id) {
            return back()->withErrors(['quantity' => 'Du kannst deinen eigenen Gegenstand nicht anfragen.']);
        }
        if (! $item->lendableTo($type)) {
            return back()->withErrors(['quantity' => 'Dieser Gegenstand wird nicht an diesen Anfragetyp verliehen.']);
        }

        $data = $request->validated();
        if ($data['quantity'] > $item->quantity) {
            return back()->withErrors(['quantity' => "Es sind insgesamt nur {$item->quantity} Stück vorhanden."]);
        }
        // Einzeltermin oder Serie (Wiederholung) in die zu buchenden Zeiträume auflösen
        $isSeries = ! empty($data['repeat']);
        $occurrences = $isSeries
            ? LoanSeries::occurrences($data['start_date'], $data['end_date'], $data['repeat'], (int) $data['repeat_count'])
            : [['start' => $data['start_date'], 'end' => $data['end_date']]];

        if ($isSeries) {
            $durationDays = (int) Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date']));
            if ($durationDays >= LoanSeries::minGapDays($data['repeat'])) {
                return back()->withErrors(['repeat' => 'Der Zeitraum ist länger als der Abstand der Wiederholung – die Termine würden sich überlappen.']);
            }
        }

        // Alles-oder-nichts: nicht verfügbare Termine sammeln und melden
        $conflicts = collect($occurrences)
            ->filter(fn ($o) => $item->availableQuantity($o['start'], $o['end']) < $data['quantity'])
            ->map(fn ($o) => Carbon::parse($o['start'])->format('d.m.Y'));
        if ($conflicts->isNotEmpty()) {
            return back()->withErrors([$isSeries ? 'repeat' : 'start_date' => $isSeries
                ? 'Nicht für alle Termine verfügbar, belegt ab: '.$conflicts->implode(', ').'. Wähle andere Termine oder weniger Wiederholungen.'
                : 'Im gewählten Zeitraum ist nicht genug Bestand verfügbar.']);
        }

        $seriesId = $isSeries ? (string) Str::uuid() : null;
        $loans = DB::transaction(fn () => collect($occurrences)->map(fn ($o) => LoanRequest::create([
            'item_id' => $item->id,
            'requester_user_id' => $user?->id,
            'requester_club_id' => $user?->club_id,
            'requester_type' => $type,
            'requester_name' => $user ? $user->club->name.' ('.$user->name.')' : $data['requester_name'],
            'requester_email' => $user?->email ?? $data['requester_email'],
            'requester_phone' => $data['requester_phone'] ?? null,
            'quantity' => $data['quantity'],
            'start_date' => $o['start'],
            'end_date' => $o['end'],
            'message' => $data['message'] ?? null,
            'status' => $user ? LoanStatus::Pending : LoanStatus::Unverified,
            'series_id' => $seriesId,
        ])));
        $loan = $loans->first();

        // Pro Serie genau eine Benachrichtigung (enthält alle Termine)
        if ($user) {
            $this->notifyOwner($loan);
        } else {
            Notification::route('mail', $loan->requester_email)->notify(new LoanRequestVerify($loan));
        }

        return redirect()->route('requests.show', $loan->token)->with('flash', $user
            ? ($isSeries ? 'Serien-Anfrage gesendet ('.$loans->count().' Termine). Der Verein meldet sich bei dir.' : 'Anfrage gesendet. Der Verein meldet sich bei dir.')
            : 'Fast geschafft! Bitte bestätige deine Anfrage über den Link in der E-Mail.');
    }

    public function show(string $token): Response
    {
        $loan = LoanRequest::where('token', $token)->with('item.club')->firstOrFail();
        $extension = $loan->extensions()->latest('id')->first();

        return Inertia::render('requests/Show', [
            'loan' => [
                'token' => $loan->token,
                'status' => $loan->status->value,
                'statusLabel' => $loan->status->label(),
                'quantity' => $loan->quantity,
                'start_date' => $loan->start_date->toDateString(),
                'end_date' => $loan->end_date->toDateString(),
                'message' => $loan->message,
                'decision_note' => $loan->decision_note,
                'item' => ['id' => $loan->item->id, 'name' => $loan->item->name, 'location' => $loan->item->location],
                'club' => ['name' => $loan->item->club->name, 'email' => $loan->item->club->email],
                'series' => $loan->series_id ? $loan->seriesLoans()->map(fn ($l) => [
                    'token' => $l->token,
                    'start_date' => $l->start_date->toDateString(),
                    'end_date' => $l->end_date->toDateString(),
                    'status' => $l->status->value,
                    'statusLabel' => $l->status->label(),
                    'current' => $l->is($loan),
                ])->values() : null,
                'canCancelSeries' => $loan->series_id && $loan->seriesLoans()->contains(fn ($l) => in_array($l->status, self::CANCELLABLE, true)),
                'canExtend' => in_array($loan->status, [LoanStatus::Approved, LoanStatus::PickedUp], true)
                    && ! $loan->pendingExtension()->exists(),
                'extension' => $extension ? [
                    'status' => $extension->status,
                    'statusLabel' => $extension->statusLabel(),
                    'requested_end_date' => $extension->requested_end_date->toDateString(),
                    'message' => $extension->message,
                    'decision_note' => $extension->decision_note,
                ] : null,
            ],
        ]);
    }

    public function verify(string $token): RedirectResponse
    {
        $loan = LoanRequest::where('token', $token)->with('item.club')->firstOrFail();

        if ($loan->status === LoanStatus::Unverified) {
            // Bei einer Serie gilt die Bestätigung für alle Termine, der Verein wird einmal informiert
            LoanRequest::where($loan->series_id ? 'series_id' : 'id', $loan->series_id ?? $loan->id)
                ->where('status', LoanStatus::Unverified->value)
                ->update(['status' => LoanStatus::Pending->value]);
            $this->notifyOwner($loan->refresh());
        }

        return redirect()->route('requests.show', $token)->with('flash', 'Danke! Deine Anfrage wurde an den Verein übermittelt.');
    }

    public function cancel(string $token): RedirectResponse
    {
        $loan = LoanRequest::where('token', $token)->firstOrFail();

        if (in_array($loan->status, [LoanStatus::Unverified, LoanStatus::Pending, LoanStatus::Approved], true)) {
            $freesStock = $loan->status === LoanStatus::Approved;
            $loan->update(['status' => LoanStatus::Cancelled]);

            if ($freesStock) {
                app(WaitlistNotifier::class)->check($loan->item);
            }
        }

        return back()->with('flash', 'Anfrage storniert.');
    }

    /** Storniert alle noch stornierbaren Termine der Serie. */
    public function cancelSeries(string $token): RedirectResponse
    {
        $loan = LoanRequest::where('token', $token)->firstOrFail();
        abort_unless($loan->series_id, 404);

        $loans = $loan->seriesLoans()->filter(fn ($l) => in_array($l->status, self::CANCELLABLE, true));
        $freesStock = $loans->contains(fn ($l) => $l->status === LoanStatus::Approved);
        $loans->each->update(['status' => LoanStatus::Cancelled]);

        if ($freesStock) {
            app(WaitlistNotifier::class)->check($loan->item);
        }

        return back()->with('flash', $loans->count().' Termin(e) der Serie storniert.');
    }

    public function extend(Request $request, string $token): RedirectResponse
    {
        $loan = LoanRequest::where('token', $token)->with('item.club')->firstOrFail();

        if (! in_array($loan->status, [LoanStatus::Approved, LoanStatus::PickedUp], true)) {
            return back()->withErrors(['requested_end_date' => 'Eine Verlängerung ist nur für genehmigte oder laufende Ausleihen möglich.']);
        }
        if ($loan->pendingExtension()->exists()) {
            return back()->withErrors(['requested_end_date' => 'Es liegt bereits eine offene Verlängerungsanfrage vor.']);
        }

        $data = $request->validate([
            'requested_end_date' => ['required', 'date', 'after:'.$loan->end_date->toDateString(), 'after_or_equal:today'],
            'message' => ['nullable', 'string', 'max:1000'],
        ], [], ['requested_end_date' => 'Neues Rückgabedatum']);

        // Vorab-Check: ist der Bestand im verlängerten Zeitraum frei?
        if ($loan->item->availableQuantity($loan->start_date->toDateString(), $data['requested_end_date'], $loan->id) < $loan->quantity) {
            return back()->withErrors(['requested_end_date' => 'Im verlängerten Zeitraum ist der Gegenstand bereits anderweitig vergeben.']);
        }

        $extension = $loan->extensions()->create([
            'previous_end_date' => $loan->end_date,
            'requested_end_date' => $data['requested_end_date'],
            'message' => $data['message'] ?? null,
        ]);

        $loan->item->club->notifyContacts(new ExtensionRequested($extension->setRelation('loanRequest', $loan)), 'extensions');

        return back()->with('flash', 'Verlängerung angefragt. Der Verein meldet sich bei dir.');
    }

    private function notifyOwner(LoanRequest $loan): void
    {
        $loan->item->club->notifyContacts(new LoanRequestReceived($loan), 'requests');
    }
}
