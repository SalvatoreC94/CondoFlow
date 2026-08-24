<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case Caretaker = 'caretaker';
    case Condomino = 'condomino';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Amministratore',
            self::Caretaker => 'Custode',
            self::Condomino => 'Condomino',
        };
    }
}
