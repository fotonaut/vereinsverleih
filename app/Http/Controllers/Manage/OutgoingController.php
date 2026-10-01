<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\LoanRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OutgoingController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->club_id, 403);

        $loans = LoanRequest::where('requester_club_id', $request->user()->club_id)
            ->with('item.club:id,name,email')
            ->latest()
            ->get()
            ->map(fn (LoanRequest $l) => $l->toArray() + ['status_label' => $l->status->label()]);

        return Inertia::render('manage/Outgoing', ['loans' => $loans]);
    }
}
