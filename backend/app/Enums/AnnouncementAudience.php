<?php

namespace App\Enums;

enum AnnouncementAudience: string
{
    case All = 'all';
    case Buildings = 'buildings';
    case Users = 'users';
}
