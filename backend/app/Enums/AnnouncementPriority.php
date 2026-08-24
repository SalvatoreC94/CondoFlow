<?php

namespace App\Enums;

enum AnnouncementPriority: string
{
    case Normal = 'normal';
    case Important = 'important';
    case Urgent = 'urgent';
}
