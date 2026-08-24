<?php

namespace App\Enums;

enum UnitUserRelationship: string
{
    case Owner = 'owner';
    case Tenant = 'tenant';
}
