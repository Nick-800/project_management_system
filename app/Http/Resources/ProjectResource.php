<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'owner' => [
                'id' => $this->owner_id,
                'name' => optional($this->owner)->name,
                'email' => optional($this->owner)->email,
            ],
            'members_count' => $this->whenCounted('members'),
            'tasks_count' => $this->whenCounted('tasks'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
