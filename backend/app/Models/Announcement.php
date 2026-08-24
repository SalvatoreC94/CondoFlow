<?php

namespace App\Models;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementPriority;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominium_id',
        'created_by',
        'title',
        'content',
        'priority',
        'audience',
        'published_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => AnnouncementPriority::class,
            'audience' => AnnouncementAudience::class,
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function condominium(): BelongsTo
    {
        return $this->belongsTo(Condominium::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function buildings(): BelongsToMany
    {
        return $this->belongsToMany(Building::class, 'announcement_building')->withTimestamps();
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_user')->withTimestamps();
    }

    public function reads(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_reads')
            ->withPivot('read_at')
            ->withTimestamps(false);
    }

    /**
     * Whether the given user is part of this announcement's audience.
     */
    public function isVisibleTo(User $user): bool
    {
        if ($user->id === $this->created_by) {
            return true;
        }

        // Assumes the caller already confirmed the user has tenant-level access
        // to this announcement's condominium; this only resolves audience targeting.
        return match ($this->audience) {
            AnnouncementAudience::All => true,
            AnnouncementAudience::Buildings => $user->units()
                ->whereIn('units.building_id', $this->buildings()->pluck('buildings.id'))
                ->exists(),
            AnnouncementAudience::Users => $this->recipients()->where('users.id', $user->id)->exists(),
        };
    }
}
