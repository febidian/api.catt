<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeleteNoteShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'note_id' => $this->note_id,
            'title' => $this->title,
            'category' => new CategoryResource($this->category),
            'date' => $this->updated_at,
        ];
    }
}
