<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\WaitlistEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WaitlistOverviewController extends Controller
{
    /** Übersicht: worauf wir warten, und wer auf unsere Gegenstände wartet. */
    public function __invoke(Request $request): Response
    {
        $clubId = $request->user()->club_id ?: abort(403);

        $own = WaitlistEntry::where('requester_club_id', $clubId)
            ->whereIn('status', ['waiting', 'notified'])
            ->with('item:id,name')
            ->orderByRaw("case status when 'notified' then 0 else 1 end")->orderBy('start_date')
            ->get();

        $forUs = WaitlistEntry::whereHas('item', fn ($q) => $q->where('club_id', $clubId))
            ->where('status', 'waiting')
            ->with('item:id,name')
            ->orderBy('created_at')
            ->get();

        return Inertia::render('manage/Waitlist', [
            'own' => $own->map->toCard()->values(),
            'forUs' => $forUs->map->toCard()->values(),
        ]);
    }
}
