<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $guarded = [];

    protected $fillable = [
        'first_name',
        'last_name',
        'company_name',
        'birthday',
        'gender',
        'phone',
        'address',
        'postal_code',
        'city',
        'email',
        'id_passport',
    ];

    protected $casts = [
        'birthday' => 'date',
    ];
    
    public function portfolio(){
        return $this->hasOne(ClientPortfolio::class);
    }
}
