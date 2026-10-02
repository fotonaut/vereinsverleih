<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Notifications\ClubTestMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class NotificationSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $club = $this->club($request);

        return Inertia::render('manage/Notifications', [
            'settings' => $club->notificationSettings(),
            'clubEmail' => $club->email,
            'adminEmails' => $club->users()->where('role', 'club_admin')->orderBy('name')->pluck('email'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $club = $this->club($request);

        $data = $request->validate([
            'requests' => ['boolean'],
            'extensions' => ['boolean'],
            'overdue' => ['boolean'],
            'recipients' => ['required', Rule::in(['both', 'club_email', 'admins'])],
            'extra_email' => ['nullable', 'email', 'max:190'],
        ], [], ['extra_email' => 'Zusätzliche E-Mail-Adresse']);

        $club->update(['notification_settings' => [
            'requests' => $request->boolean('requests'),
            'extensions' => $request->boolean('extensions'),
            'overdue' => $request->boolean('overdue'),
            'recipients' => $data['recipients'],
            'extra_email' => $data['extra_email'] ?? null,
        ]]);

        return back()->with('flash', 'Benachrichtigungs-Einstellungen gespeichert.');
    }

    /** Test-Mail an alle aktuell eingestellten Empfänger. */
    public function test(Request $request): RedirectResponse
    {
        $club = $this->club($request);
        $count = $club->sendTo(new ClubTestMail($club->name));

        return back()->with('flash', "Test-Mail an {$count} Adresse(n) gesendet (Zustellung kann bis zu 5 Minuten dauern).");
    }

    private function club(Request $request)
    {
        abort_unless($request->user()->isClubAdmin(), 403);

        return $request->user()->club;
    }
}
