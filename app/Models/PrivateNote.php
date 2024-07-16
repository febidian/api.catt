<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrivateNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_private_id',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected $table = 'private';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_private_id', 'private_id');
    }
}
