<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'note_id',
        'category_id',
        'title',
        'note_content',
        'duplicate_id',
        'star_note_id',
        'deleted_at'
    ];

    protected $dates = ['deleted_at'];

    public function stars()
    {
        return $this->hasOne(Star::class, "star_id", "star_note_id");
    }

    public function category()
    {
        return $this->hasOne(Catagories::class, "category_id", "category_id");
    }
}
