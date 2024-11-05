<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Insurance extends Model
{
    protected $fillable = [
        'client_portfolio_id',
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
    protected $hidden = [
        'client_portfolio_id',
    ];

    protected $casts = [
        'inception_date' => 'date',
        'expiration_date' => 'date',    
    ];

    public function portfolio()
    {
        return $this->belongsTo(ClientPortfolio::class);
    }
}
