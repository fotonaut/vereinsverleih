<?php

namespace App\Enums;

enum UserRole: string
{
    case ClubAdmin = 'club_admin';
    case Member = 'member';
}
