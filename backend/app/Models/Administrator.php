<?php

namespace App\Models;

use App\Enums\UserRole;

/**
 * A `User` scoped to role=administrator — the paying customer of the SaaS.
 * Exists purely so the platform panel (Filament) can browse/manage
 * administrators through a model whose queries are always pre-scoped,
 * without touching the shared `User` model used everywhere else in the API.
 */
class Administrator extends User
{
    protected $table = 'users';

    protected static function booted(): void
    {
        static::addGlobalScope('administrator', function ($query) {
            $query->where('role', UserRole::Administrator->value);
        });

        static::creating(function (self $administrator) {
            $administrator->role = UserRole::Administrator;
        });
    }
}
