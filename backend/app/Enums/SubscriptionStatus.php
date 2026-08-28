<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Trial => 'Prova',
            self::Active => 'Attivo',
            self::Suspended => 'Sospeso',
            self::Cancelled => 'Annullato',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Trial => 'info',
            self::Active => 'success',
            self::Suspended => 'warning',
            self::Cancelled => 'danger',
        };
    }
}
