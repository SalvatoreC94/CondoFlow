<?php

namespace App\Enums;

enum UnitType: string
{
    case Apartment = 'apartment';
    case Garage = 'garage';
    case Cellar = 'cellar';
    case Shop = 'shop';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Apartment => 'Appartamento',
            self::Garage => 'Box auto',
            self::Cellar => 'Cantina',
            self::Shop => 'Negozio',
            self::Other => 'Altro',
        };
    }
}
