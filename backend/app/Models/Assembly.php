<?php

namespace App\Models;

use App\Enums\AssemblyStatus;
use App\Enums\AssemblyType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assembly extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominium_id',
        'created_by',
        'minutes_document_id',
        'title',
        'type',
        'status',
        'agenda',
        'location',
        'scheduled_at',
        'held_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => AssemblyType::class,
            'status' => AssemblyStatus::class,
            'scheduled_at' => 'datetime',
            'held_at' => 'datetime',
        ];
    }

    public function condominium(): BelongsTo
    {
        return $this->belongsTo(Condominium::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function minutesDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'minutes_document_id');
    }

    public function resolutions(): HasMany
    {
        return $this->hasMany(AssemblyResolution::class)->orderBy('sort_order');
    }
}
