<?php

namespace App\Models;

use App\Enums\ResolutionOutcome;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssemblyResolution extends Model
{
    use HasFactory;

    protected $fillable = [
        'assembly_id',
        'description',
        'outcome',
        'notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'outcome' => ResolutionOutcome::class,
        ];
    }

    public function assembly(): BelongsTo
    {
        return $this->belongsTo(Assembly::class);
    }
}
