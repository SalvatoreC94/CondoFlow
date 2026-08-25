<?php

namespace App\Enums;

enum AssemblyType: string
{
    case Ordinary = 'ordinary';
    case Extraordinary = 'extraordinary';

    public function label(): string
    {
        return match ($this) {
            self::Ordinary => 'Ordinaria',
            self::Extraordinary => 'Straordinaria',
        };
    }
}
