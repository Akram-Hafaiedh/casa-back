<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDocument extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'path',
        'type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
