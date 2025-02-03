<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vacation extends Model
{
    protected $table = 'user_vacations';

    const APPROVED = 1;
    const PENDING = 2;
    const REJECTED = 3;
    
    protected $fillable = [
        'user_id',
        'title',
        'status',
        'start',
        'end',
        'description',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
