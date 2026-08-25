<?php

namespace App\Models;

use App\Enums\UnitType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominium_id',
        'building_id',
        'code',
        'floor',
        'type',
        'surface_sqm',
        'millesimi',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => UnitType::class,
            'surface_sqm' => 'decimal:2',
            'millesimi' => 'decimal:3',
        ];
    }

    public function condominium(): BelongsTo
    {
        return $this->belongsTo(Condominium::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'unit_user')
            ->withPivot(['relationship', 'is_primary'])
            ->withTimestamps();
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function installmentCharges(): HasMany
    {
        return $this->hasMany(InstallmentCharge::class);
    }
}
