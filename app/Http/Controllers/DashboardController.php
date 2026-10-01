<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\LoanRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $clubId = $request->user()->club_id;

        return Inertia::render('Dashboard', [
            'stats' => [
                'items' => Item::where('club_id', $clubId)->count(),
                'pending' => LoanRequest::whereHas('item', fn ($q) => $q->where('club_id', $clubId))->where('status', 'pending')->count(),
                'active' => LoanRequest::whereHas('item', fn ($q) => $q->where('club_id', $clubId))->whereIn('status', ['approved', 'picked_up'])->count(),
                'outgoing' => LoanRequest::where('requester_club_id', $clubId)->whereIn('status', ['pending', 'approved', 'picked_up'])->count(),
            ],
        ]);
    }
}
