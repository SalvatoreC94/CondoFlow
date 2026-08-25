<?php

namespace App\Enums;

enum AssemblyStatus: string
{
    case Scheduled = 'scheduled';
    case Held = 'held';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Convocata',
            self::Held => 'Svolta',
            self::Cancelled => 'Annullata',
        };
    }
}
