<?php

namespace App\Enums;

enum SplitMethod: string
{
    case Millesimi = 'millesimi';
    case Equal = 'equal';

    public function label(): string
    {
        return match ($this) {
            self::Millesimi => 'Per millesimi',
            self::Equal => 'In parti uguali',
        };
    }
}
