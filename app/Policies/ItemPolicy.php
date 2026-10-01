<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    public function manage(User $user, Item $item): bool
    {
        return $user->club_id !== null && $user->club_id === $item->club_id;
    }

    public function create(User $user): bool
    {
        return $user->club_id !== null;
    }
}
