<?php

namespace App\Http\Controllers\Manage;

use App\Enums\LoanStatus;
use App\Http\Controllers\Controller;
use App\Models\LoanExtension;
use App\Models\LoanRequest;
use App\Notifications\ExtensionDecided;
use App\Services\WaitlistNotifier;
use App\Notifications\LoanRequestDecided;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class IncomingController extends Controller
{
    /** Erlaubte Statusübergänge. */
    private const TRANSITIONS = [
        'pending' => ['approved', 'declined'],
        'approved' => ['picked_up', 'declined', 'cancelled'],
        'picked_up' => ['returned'],
    ];

    public function index(Request $request): Response
    {
        abort_unless($request->user()->club_id, 403);

        $loans = LoanRequest::whereHas('item', fn ($q) => $q->where('club_id', $request->user()->club_id))
            ->where('status', '!=', LoanStatus::Unverified->value)
            ->with(['item:id,name,quantity', 'pendingExtension'])
            ->orderByRaw("case status when 'pending' then 0 when 'approved' then 1 when 'picked_up' then 2 else 3 end")
            ->orderBy('start_date')
            ->get()
            ->map(fn (LoanRequest $l) => $l->toArray() + [
                'status_label' => $l->status->label(),
                'overdue' => $l->isOverdue(),
                'pending_extension' => $l->pendingExtension,
                'next' => self::TRANSITIONS[$l->status->value] ?? [],
            ]);

        return Inertia::render('manage/Incoming', ['loans' => $loans]);
    }

    public function update(Request $request, LoanRequest $loanRequest): RedirectResponse
    {
        Gate::authorize('decide', $loanRequest);

        $data = $request->validate([
            'status' => ['required', 'in:approved,declined,picked_up,returned,cancelled'],
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $allowed = self::TRANSITIONS[$loanRequest->status->value] ?? [];
        if (! in_array($data['status'], $allowed, true)) {
            return back()->withErrors(['status' => 'Dieser Statuswechsel ist nicht möglich.']);
        }

        if ($data['status'] === 'approved') {
            $free = $loanRequest->item->availableQuantity(
                $loanRequest->start_date->toDateString(),
                $loanRequest->end_date->toDateString(),
                $loanRequest->id,
            );
            if ($free < $loanRequest->quantity) {
                return back()->withErrors(['status' => 'Im Zeitraum ist der Bestand durch andere Genehmigungen belegt.']);
            }
        }

        $loanRequest->update([
            'status' => $data['status'],
            'decision_note' => $data['decision_note'] ?? $loanRequest->decision_note,
        ]);

        if (in_array($data['status'], ['approved', 'declined', 'cancelled'], true)) {
            Notification::route('mail', $loanRequest->requester_email)->notify(new LoanRequestDecided($loanRequest->load('item.club')));
        }

        // Wird dadurch Bestand frei, die Warteliste informieren
        if (in_array($data['status'], ['declined', 'cancelled', 'returned'], true)) {
            app(WaitlistNotifier::class)->check($loanRequest->item);
        }

        return back()->with('flash', 'Status aktualisiert: '.$loanRequest->status->label());
    }

    public function decideExtension(Request $request, LoanRequest $loanRequest, LoanExtension $extension): RedirectResponse
    {
        Gate::authorize('decide', $loanRequest);
        abort_unless($extension->loan_request_id === $loanRequest->id && $extension->status === 'pending', 404);

        $data = $request->validate([
            'decision' => ['required', 'in:approved,declined'],
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($data['decision'] === 'approved') {
            $free = $loanRequest->item->availableQuantity(
                $loanRequest->start_date->toDateString(),
                $extension->requested_end_date->toDateString(),
                $loanRequest->id,
            );
            if ($free < $loanRequest->quantity) {
                return back()->withErrors(['status' => 'Im verlängerten Zeitraum ist der Bestand durch andere Genehmigungen belegt.']);
            }

            // neues Enddatum übernehmen; Erinnerung/Mahnung beginnen für den neuen Zeitraum von vorn
            $loanRequest->update([
                'end_date' => $extension->requested_end_date,
                'reminded_at' => null,
                'overdue_notified_at' => null,
                'overdue_count' => 0,
            ]);
        }

        $extension->update(['status' => $data['decision'], 'decision_note' => $data['decision_note'] ?? null]);

        Notification::route('mail', $loanRequest->requester_email)
            ->notify(new ExtensionDecided($extension->setRelation('loanRequest', $loanRequest->load('item.club'))));

        return back()->with('flash', $data['decision'] === 'approved' ? 'Verlängerung genehmigt.' : 'Verlängerung abgelehnt.');
    }
}
