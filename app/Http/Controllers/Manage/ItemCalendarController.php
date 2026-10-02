<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ItemCalendarController extends Controller
{
    /** Belegungskalender eines eigenen Gegenstands inkl. Namen und offener Anfragen. */
    public function __invoke(Item $item): Response
    {
        Gate::authorize('manage', $item);

        $loans = $item->loanRequests()
            ->whereIn('status', ['pending', 'approved', 'picked_up'])
            ->whereDate('end_date', '>=', today()->subMonth())
            ->orderBy('start_date')
            ->get();

        return Inertia::render('manage/items/Calendar', [
            'item' => $item->only(['id', 'name', 'quantity']),
            'reservations' => $loans->map(fn ($l) => [
                'start_date' => $l->start_date->toDateString(),
                'end_date' => $l->end_date->toDateString(),
                'quantity' => $l->quantity,
                'pending' => $l->status->value === 'pending',
                'label' => $l->requester_name,
            ])->values(),
            'waitlist' => $item->waitlistEntries()->where('status', 'waiting')->orderBy('created_at')->get()
                ->map(fn ($w) => [
                    'id' => $w->id,
                    'requester' => $w->requester_name,
                    'quantity' => $w->quantity,
                    'start_date' => $w->start_date->toDateString(),
                    'end_date' => $w->end_date->toDateString(),
                    'series' => $w->isSeries() ? $w->repeat_count.'× '.\App\Support\LoanSeries::INTERVALS[$w->repeat] : null,
                ])->values(),
            'returns' => $item->loanRequests()->with('returnPhotos')->where('status', 'returned')->whereNotNull('returned_at')
                ->orderByDesc('returned_at')->limit(8)->get()
                ->map(fn ($l) => [
                    'id' => $l->id,
                    'requester' => $l->requester_name,
                    'returned_at' => $l->returned_at->toDateString(),
                    'condition' => $l->return_condition?->value,
                    'condition_label' => $l->return_condition?->label(),
                    'attention' => (bool) $l->return_condition?->needsAttention(),
                    'note' => $l->return_note,
                    'deposit_returned' => $l->deposit_returned,
                    'photos' => $l->returnPhotos->map(fn ($p) => ['id' => $p->id, 'url' => route('manage.return-photo', $p)])->values(),
                ])->values(),
            'loans' => $loans->map(fn ($l) => [
                'id' => $l->id,
                'requester' => $l->requester_name,
                'status' => $l->status->value,
                'status_label' => $l->status->label(),
                'quantity' => $l->quantity,
                'start_date' => $l->start_date->toDateString(),
                'end_date' => $l->end_date->toDateString(),
            ])->values(),
        ]);
    }
}
