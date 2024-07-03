<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteShowResource extends JsonResource
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
            'note_content' => $this->note_content,
            'category' => new CategoryResource($this->category),
            'star' => new StarResource($this->stars),
            'date' => $this->updated_at,
        ];
    }
}
