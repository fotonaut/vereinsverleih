<?php

namespace App\Http\Controllers\Manage;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->isClubAdmin(), 403);

        return Inertia::render('manage/Members', [
            'members' => $request->user()->club->users()->orderBy('name')->get(['id', 'name', 'email', 'role']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isClubAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        $member = User::create($data + [
            'club_id' => $request->user()->club_id,
            'password' => Str::random(40),
        ]);
        $member->markEmailAsVerified();

        // Einladung: das neue Mitglied setzt sein Passwort über den Reset-Link
        Password::sendResetLink(['email' => $member->email]);

        return back()->with('flash', 'Einladung an '.$member->email.' versendet.');
    }

    public function destroy(Request $request, User $member): RedirectResponse
    {
        abort_unless($request->user()->isClubAdmin() && $member->club_id === $request->user()->club_id, 403);

        if ($member->is($request->user())) {
            return back()->withErrors(['member' => 'Du kannst dich nicht selbst entfernen.']);
        }

        $member->delete();

        return back()->with('flash', 'Mitglied entfernt.');
    }
}
