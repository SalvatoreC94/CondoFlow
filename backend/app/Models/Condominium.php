<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Condominium extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'condominiums';

    protected $fillable = [
        'administrator_id',
        'name',
        'slug',
        'address',
        'city',
        'province',
        'postal_code',
        'country',
        'total_units',
        'description',
        'logo_path',
        'logo_mime_type',
        'brand_color',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $condominium) {
            if (empty($condominium->slug)) {
                $condominium->slug = static::uniqueSlug($condominium->name);
            }
        });
    }

    protected static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administrator_id');
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function caretakers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'caretaker_condominium')->withTimestamps();
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'supplier_condominium')->withTimestamps();
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    public function assemblies(): HasMany
    {
        return $this->hasMany(Assembly::class);
    }

    /**
     * All users (condomini) that live in this condominium, through their units.
     */
    public function residents()
    {
        return User::whereHas('units', function ($query) {
            $query->where('units.condominium_id', $this->id);
        });
    }
}
