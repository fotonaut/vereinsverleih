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
