<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Star extends Model
{
    use HasFactory;

    protected $fillable = [
        'star_id',
        'star',
    ];

    public function notes()
    {
        return $this->BelongsTo(Note::class, "star_id", "star_note_id");
    }
}
