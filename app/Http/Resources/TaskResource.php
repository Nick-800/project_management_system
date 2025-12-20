<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
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
            'project_id' => $this->project_id,
            'assignee' => $this->when($this->relationLoaded('assignee'), function () {
                return [
                    'id' => $this->assignee?->id,
                    'name' => $this->assignee?->name,
                    'email' => $this->assignee?->email,
                ];
            }),
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->due_date,
            'completed_at' => $this->completed_at,
            'parent_id' => $this->parent_id,
            'tags' => $this->when($this->relationLoaded('tags'), fn () => $this->tags->pluck('name')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
