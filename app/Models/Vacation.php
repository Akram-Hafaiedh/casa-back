<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vacation extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'status',
        'start',
        'end',
        'comment',
    ];

    protected $hidden = [
        'user_id',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
