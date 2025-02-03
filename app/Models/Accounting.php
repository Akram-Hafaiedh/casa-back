<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Accounting extends Model
{

    protected $table = 'client_accountings';
    
    protected $fillable = [
        'client_id',
        'contract_start_date',
        'tax_included',
        'status',
    ];

    protected $casts = [
        'contract_start_date' => 'date',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }


    // public function documents()
    // {
    //     return $this->morphMany(ClientDocument::class, 'documentable');
    // }
}
