<?php

namespace App\Enums;

enum ResolutionOutcome: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Postponed = 'postponed';

    public function label(): string
    {
        return match ($this) {
            self::Approved => 'Approvata',
            self::Rejected => 'Respinta',
            self::Postponed => 'Rinviata',
        };
    }
}
