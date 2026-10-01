<?php

namespace App\Policies;

use App\Models\LoanRequest;
use App\Models\User;

class LoanRequestPolicy
{
    /** Der verleihende Verein entscheidet über Anfragen. */
    public function decide(User $user, LoanRequest $request): bool
    {
        return $user->club_id !== null && $user->club_id === $request->item->club_id;
    }

    /** Der anfragende Verein darf eigene offene Anfragen stornieren. */
    public function cancel(User $user, LoanRequest $request): bool
    {
        return $user->club_id !== null && $user->club_id === $request->requester_club_id;
    }
}
