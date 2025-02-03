<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function insurances(): HasMany
    {
        return $this->hasMany(Insurance::class);
    }

    
    public function accountings() : HasMany
    {
        return $this->hasMany(Accounting::class);
    }

    public function taxes() : HasMany
    {
        return $this->hasMany(Tax::class);
    }

    // public function documents()
    // {
    //     return $this->hasMany(ClientDocument::class);
    // }
    
    // public function portfolio(){
    //     return $this->hasOne(ClientPortfolio::class);
    // }
}
