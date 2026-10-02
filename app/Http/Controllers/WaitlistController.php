<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoanRequest;
use App\Models\Item;
use App\Models\WaitlistEntry;
use App\Notifications\WaitlistVerify;
use App\Support\LoanSeries;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class WaitlistController extends Controller
{
    private const OPEN = ['unverified', 'waiting'];

    public function store(StoreLoanRequest $request, Item $item): RedirectResponse
    {
        abort_unless($item->active, 404);

        $user = $request->user();
        if ($user && ! $user->club_id) {
            abort(403);
        }
        $type = $user ? 'club' : 'private';

        if ($user?->club_id === $item->club_id) {
            return back()->withErrors(['quantity' => 'Das ist euer eigener Gegenstand.']);
        }
        if (! $item->lendableTo($type)) {
            return back()->withErrors(['quantity' => 'Dieser Gegenstand wird nicht an diesen Anfragetyp verliehen.']);
        }

        $data = $request->validated();
        if ($data['quantity'] > $item->quantity) {
            return back()->withErrors(['quantity' => "Es sind insgesamt nur {$item->quantity} Stück vorhanden."]);
        }
        $repeat = $data['repeat'] ?? null;
        $count = $repeat ? (int) $data['repeat_count'] : null;
        $occurrences = $repeat
            ? LoanSeries::occurrences($data['start_date'], $data['end_date'], $repeat, $count)
            : [['start' => $data['start_date'], 'end' => $data['end_date']]];

        if ($repeat) {
            $durationDays = (int) Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date']));
            if ($durationDays >= LoanSeries::minGapDays($repeat)) {
                return back()->withErrors(['repeat' => 'Der Zeitraum ist länger als der Abstand der Wiederholung – die Termine würden sich überlappen.']);
            }
        }

        // Nur sinnvoll, wenn mindestens ein Termin belegt ist; sonst direkt anfragen
        $anyTaken = collect($occurrences)->contains(fn ($o) => $item->availableQuantity($o['start'], $o['end']) < $data['quantity']);
        if (! $anyTaken) {
            return back()->withErrors([$repeat ? 'repeat' : 'start_date' => $repeat
                ? 'Alle Termine sind frei – du kannst die Serie direkt anfragen.'
                : 'Der Gegenstand ist in diesem Zeitraum frei – du kannst ihn direkt anfragen.']);
        }

        $email = $user?->email ?? $data['requester_email'];
        $until = Arr::last($occurrences)['end'];
        $duplicate = WaitlistEntry::where('item_id', $item->id)->where('requester_email', $email)
            ->whereIn('status', self::OPEN)
            ->whereDate('start_date', '<=', $until)->whereDate('until_date', '>=', $data['start_date'])
            ->exists();
        if ($duplicate) {
            return back()->withErrors([$repeat ? 'repeat' : 'start_date' => 'Du stehst für einen überlappenden Zeitraum bereits auf der Warteliste.']);
        }

        $entry = WaitlistEntry::create([
            'item_id' => $item->id,
            'requester_user_id' => $user?->id,
            'requester_club_id' => $user?->club_id,
            'requester_type' => $type,
            'requester_name' => $user ? $user->club->name.' ('.$user->name.')' : $data['requester_name'],
            'requester_email' => $email,
            'quantity' => $data['quantity'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'repeat' => $repeat,
            'repeat_count' => $count,
            'until_date' => $until,
            'status' => $user ? 'waiting' : 'unverified',
        ]);

        if (! $user) {
            Notification::route('mail', $email)->notify(new WaitlistVerify($entry->setRelation('item', $item)));
        }

        return redirect()->route('waitlist.show', $entry->token)->with('flash', $user
            ? 'Du stehst auf der Warteliste. Wir melden uns, sobald der Gegenstand frei wird.'
            : 'Fast geschafft! Bitte bestätige den Eintrag über den Link in der E-Mail.');
    }

    public function show(string $token): Response
    {
        $entry = WaitlistEntry::where('token', $token)->with('item.club')->firstOrFail();

        return Inertia::render('waitlist/Show', [
            'entry' => [
                'token' => $entry->token,
                'status' => $entry->status,
                'statusLabel' => $entry->statusLabel(),
                'quantity' => $entry->quantity,
                'start_date' => $entry->start_date->toDateString(),
                'end_date' => $entry->end_date->toDateString(),
                'series' => $entry->isSeries() ? ['label' => LoanSeries::INTERVALS[$entry->repeat], 'count' => $entry->repeat_count, 'until' => $entry->until_date->toDateString()] : null,
                'item' => ['id' => $entry->item->id, 'name' => $entry->item->name, 'club' => $entry->item->club->name],
                'open' => in_array($entry->status, self::OPEN, true),
            ],
        ]);
    }

    public function verify(string $token): RedirectResponse
    {
        $entry = WaitlistEntry::where('token', $token)->firstOrFail();

        if ($entry->status === 'unverified') {
            $entry->update(['status' => 'waiting']);
        }

        return redirect()->route('waitlist.show', $token)->with('flash', 'Danke! Du stehst auf der Warteliste.');
    }

    public function cancel(string $token): RedirectResponse
    {
        $entry = WaitlistEntry::where('token', $token)->firstOrFail();

        if (in_array($entry->status, self::OPEN, true)) {
            $entry->update(['status' => 'cancelled']);
        }

        return back()->with('flash', 'Du wurdest von der Warteliste abgemeldet.');
    }
}
