<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\LoanRequest;
use App\Models\WaitlistEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $clubId = $request->user()->club_id;

        // Eigene Wartelisten-Einträge: offene und kürzlich benachrichtigte (Frist 14 Tage)
        $ownWaitlist = WaitlistEntry::where('requester_club_id', $clubId)
            ->where(fn ($q) => $q->where('status', 'waiting')
                ->orWhere(fn ($n) => $n->where('status', 'notified')->where('notified_at', '>=', now()->subDays(14))))
            ->with('item:id,name')
            ->orderByRaw("case status when 'notified' then 0 else 1 end")->orderBy('start_date');

        return Inertia::render('Dashboard', [
            'stats' => [
                'items' => Item::where('club_id', $clubId)->count(),
                'pending' => LoanRequest::whereHas('item', fn ($q) => $q->where('club_id', $clubId))->where('status', 'pending')->count(),
                'active' => LoanRequest::whereHas('item', fn ($q) => $q->where('club_id', $clubId))->whereIn('status', ['approved', 'picked_up'])->count(),
                'waiting_for_us' => WaitlistEntry::whereHas('item', fn ($q) => $q->where('club_id', $clubId))->where('status', 'waiting')->count(),
                'outgoing' => LoanRequest::where('requester_club_id', $clubId)->whereIn('status', ['pending', 'approved', 'picked_up'])->count(),
            ],
            'waitlist' => (clone $ownWaitlist)->limit(5)->get()->map->toCard()->values(),
            'waitlistTotal' => $ownWaitlist->count(),
        ]);
    }
}
