<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'condominium_id' => $this->condominium_id,
            'category' => new DocumentCategoryResource($this->whenLoaded('category')),
            'title' => $this->title,
            'description' => $this->description,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'visibility' => $this->visibility->value,
            'uploader' => new UserResource($this->whenLoaded('uploader')),
            'download_url' => route('documents.download', ['document' => $this->id]),
            'published_at' => $this->published_at,
        ];
    }
}
