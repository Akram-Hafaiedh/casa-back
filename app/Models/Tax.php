<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tax extends Model
{
    protected $guarded = [];

    protected $fillable = [
        'name',
        'percentage',
        'type',
        'documents',
    ];


    public function portfolio() : BelongsTo
    {
        return $this->belongsTo(ClientPortfolio::class);
    }
}
