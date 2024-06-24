<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'note_id',
        'category_id',
        'title',
        'note_content',
        'star_note_id',
    ];

    public function stars()
    {
        return $this->hasOne(Star::class, "star_id", "star_note_id");
    }

    public function category()
    {
        return $this->hasOne(Catagories::class, "category_id", "category_id");
    }
}
