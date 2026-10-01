<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClubController extends Controller
{
    public function edit(Request $request): Response
    {
        $this->ensureAdmin($request);

        return Inertia::render('manage/Club', ['club' => $request->user()->club]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:3000'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:50'],
            'street' => ['nullable', 'string', 'max:150'],
            'zip' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:190'],
        ]);

        $request->user()->club->update($data);

        return back()->with('flash', 'Vereinsdaten gespeichert.');
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()->isClubAdmin(), 403);
    }
}
