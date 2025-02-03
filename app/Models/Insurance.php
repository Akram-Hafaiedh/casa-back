<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Insurance extends Model
{
    protected $table = 'client_insurances';
    
    protected $fillable = [
        'client_id',
        'type',
        'agency',
        'policy_number',
        'inception_date',
        'expiration_date',
        'status',
        'cancellation_period',
        'payment_amount',
        'payment_frequency',
    ];

    protected $casts = [
        'inception_date' => 'date',
        'expiration_date' => 'date',    
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
