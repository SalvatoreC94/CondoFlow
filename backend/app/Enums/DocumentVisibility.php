<?php

namespace App\Enums;

enum DocumentVisibility: string
{
    case All = 'all';
    case Administrators = 'administrators';
    case Caretakers = 'caretakers';
    case Condomini = 'condomini';
}
