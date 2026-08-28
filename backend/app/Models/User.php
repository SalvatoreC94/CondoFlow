<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasPushSubscriptions, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'status',
        'invited_by',
        'invitation_token',
        'invitation_expires_at',
        'invitation_accepted_at',
        'subscription_status',
        'subscription_plan',
        'subscription_ends_at',
        'subscription_notes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'invitation_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'invitation_expires_at' => 'datetime',
            'invitation_accepted_at' => 'datetime',
            'subscription_status' => SubscriptionStatus::class,
            'subscription_ends_at' => 'datetime',
        ];
    }

    public function isAdministrator(): bool
    {
        return $this->role === UserRole::Administrator;
    }

    public function isCaretaker(): bool
    {
        return $this->role === UserRole::Caretaker;
    }

    public function isCondomino(): bool
    {
        return $this->role === UserRole::Condomino;
    }

    /**
     * Condominiums owned/managed by this user (only meaningful for administrators).
     */
    public function administeredCondominiums(): HasMany
    {
        return $this->hasMany(Condominium::class, 'administrator_id');
    }

    /**
     * Condominiums this user (caretaker) is assigned to.
     */
    public function assignedCondominiums(): BelongsToMany
    {
        return $this->belongsToMany(Condominium::class, 'caretaker_condominium')->withTimestamps();
    }

    /**
     * Units this user (condomino) belongs to.
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'unit_user')
            ->withPivot(['relationship', 'is_primary'])
            ->withTimestamps();
    }

    public function ticketsCreated(): HasMany
    {
        return $this->hasMany(Ticket::class, 'created_by');
    }

    public function ticketsAssigned(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_caretaker_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Every condominium this user has any visibility into, regardless of role.
     * Used as the core multi-tenancy boundary.
     *
     * @return Collection<int, int>
     */
    public function visibleCondominiumIds(): Collection
    {
        return match ($this->role) {
            UserRole::Administrator => $this->administeredCondominiums()->pluck('id'),
            UserRole::Caretaker => $this->assignedCondominiums()->pluck('condominiums.id'),
            UserRole::Condomino => $this->units()->pluck('units.condominium_id')->unique()->values(),
            default => collect(),
        };
    }
}
