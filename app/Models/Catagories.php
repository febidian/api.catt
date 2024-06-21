<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Catagories extends Model
{
    use HasFactory;

    protected $table = "categories";

    protected $fillable = [
        'category_id',
        'category_name',
        'user_id',
    ];

    public function category()
    {
        return $this->belongsTo(Note::class, "category_id", "category_id");
    }
}
