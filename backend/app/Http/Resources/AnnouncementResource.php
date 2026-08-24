<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnouncementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'condominium_id' => $this->condominium_id,
            'title' => $this->title,
            'content' => $this->content,
            'priority' => $this->priority->value,
            'audience' => $this->audience->value,
            'author' => new UserResource($this->whenLoaded('author')),
            'buildings' => BuildingResource::collection($this->whenLoaded('buildings')),
            'published_at' => $this->published_at,
            'expires_at' => $this->expires_at,
            'is_read' => $user ? $this->reads()->where('users.id', $user->id)->exists() : false,
            'created_at' => $this->created_at,
        ];
    }
}
