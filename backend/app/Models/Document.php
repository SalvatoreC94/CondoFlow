<?php

namespace App\Models;

use App\Enums\DocumentVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominium_id',
        'document_category_id',
        'uploaded_by',
        'title',
        'description',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'visibility',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'visibility' => DocumentVisibility::class,
            'published_at' => 'datetime',
        ];
    }

    public function condominium(): BelongsTo
    {
        return $this->belongsTo(Condominium::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'document_category_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
