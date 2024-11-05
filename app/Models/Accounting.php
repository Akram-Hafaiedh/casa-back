<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Accounting extends Model
{
    protected $fillable = [
        'portfolio_id',
        'contract_start_date',
        'tax_included',
        'documents',
    ];

    protected $casts = [
        'contract_start_date' => 'date',
    ];

    protected $hidden = [
        'portfolio_id',
        'created_at',
        'updated_at',
    ];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(ClientPortfolio::class);
    }
}
