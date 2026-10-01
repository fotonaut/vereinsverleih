<?php

namespace App\Http\Controllers;

use App\Enums\LoanStatus;
use App\Http\Requests\StoreLoanRequest;
use App\Models\Item;
use App\Models\LoanRequest;
use App\Notifications\LoanRequestReceived;
use App\Notifications\LoanRequestVerify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class LoanRequestController extends Controller
{
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
        if ($item->availableQuantity($data['start_date'], $data['end_date']) < $data['quantity']) {
            return back()->withErrors(['start_date' => 'Im gewählten Zeitraum ist nicht genug Bestand verfügbar.']);
        }

        $loan = LoanRequest::create([
            'item_id' => $item->id,
            'requester_user_id' => $user?->id,
            'requester_club_id' => $user?->club_id,
            'requester_type' => $type,
            'requester_name' => $user ? $user->club->name.' ('.$user->name.')' : $data['requester_name'],
            'requester_email' => $user?->email ?? $data['requester_email'],
            'requester_phone' => $data['requester_phone'] ?? null,
            'quantity' => $data['quantity'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'message' => $data['message'] ?? null,
            'status' => $user ? LoanStatus::Pending : LoanStatus::Unverified,
        ]);

        if ($user) {
            $this->notifyOwner($loan);
        } else {
            Notification::route('mail', $loan->requester_email)->notify(new LoanRequestVerify($loan));
        }

        return redirect()->route('requests.show', $loan->token)->with('flash', $user
            ? 'Anfrage gesendet. Der Verein meldet sich bei dir.'
            : 'Fast geschafft! Bitte bestätige deine Anfrage über den Link in der E-Mail.');
    }

    public function show(string $token): Response
    {
        $loan = LoanRequest::where('token', $token)->with('item.club')->firstOrFail();

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
            ],
        ]);
    }

    public function verify(string $token): RedirectResponse
    {
        $loan = LoanRequest::where('token', $token)->with('item.club')->firstOrFail();

        if ($loan->status === LoanStatus::Unverified) {
            $loan->update(['status' => LoanStatus::Pending]);
            $this->notifyOwner($loan);
        }

        return redirect()->route('requests.show', $token)->with('flash', 'Danke! Deine Anfrage wurde an den Verein übermittelt.');
    }

    public function cancel(string $token): RedirectResponse
    {
        $loan = LoanRequest::where('token', $token)->firstOrFail();

        if (in_array($loan->status, [LoanStatus::Unverified, LoanStatus::Pending, LoanStatus::Approved], true)) {
            $loan->update(['status' => LoanStatus::Cancelled]);
        }

        return back()->with('flash', 'Anfrage storniert.');
    }

    private function notifyOwner(LoanRequest $loan): void
    {
        $club = $loan->item->club;
        $recipients = $club->users()->where('role', 'club_admin')->get();

        Notification::route('mail', $club->email)->notify(new LoanRequestReceived($loan));
        // Club-Admins, deren E-Mail nicht die Vereinsadresse ist, ebenfalls informieren
        foreach ($recipients->where('email', '!=', $club->email) as $admin) {
            $admin->notify(new LoanRequestReceived($loan));
        }
    }
}
