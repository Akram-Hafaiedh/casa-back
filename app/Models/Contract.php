<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{

    protected $table = 'user_contracts';

    protected $fillable = [
        'user_id',
        'type',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
