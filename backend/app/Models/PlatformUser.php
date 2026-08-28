<?php

namespace App\Models;

use Database\Factories\PlatformUserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * The CondoFlow platform operator — not a tenant, not a condominium
 * administrator. Authenticated through its own guard (`platform`), used
 * only to log into the Filament panel at /platform.
 */
class PlatformUser extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<PlatformUserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Every row in `platform_users` is a platform operator by definition
     * (there is no separate role/permission tier yet) — being able to
     * authenticate on the `platform` guard is itself the access grant.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
